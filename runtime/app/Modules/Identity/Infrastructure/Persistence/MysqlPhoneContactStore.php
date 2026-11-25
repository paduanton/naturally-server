<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\PhoneContactData;
use App\Modules\Identity\Application\PhoneContactSession;
use App\Modules\Identity\Application\PhoneContactStore;
use App\Modules\Identity\Domain\PhoneContact;
use App\Modules\Identity\Domain\PhoneContactConflict;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class MysqlPhoneContactStore implements PhoneContactStore
{
    public function listForSession(PhoneContactSession $session): array
    {
        return DB::transaction(function () use ($session): array {
            $this->lockActiveSession($session);
            return Phone::where('user_id', $session->actorId)->orderBy('id')->get()->map(fn (Phone $phone): PhoneContactData => $this->data($phone))->all();
        });
    }

    public function addForSession(PhoneContactSession $session, PhoneContact $contact): PhoneContactData
    {
        try {
            return DB::transaction(function () use ($session, $contact): PhoneContactData {
                $this->lockActiveSession($session);
                $existing = Phone::where('user_id', $session->actorId)->orderBy('id')->lockForUpdate()->get();
                $contact->assertFitsCollection($existing->count(), $existing->contains('main', true));
                $phone = new Phone();
                $phone->forceFill(['user_id' => $session->actorId, 'label' => $contact->label, 'country_code' => $contact->countryCode, 'number' => $contact->number, 'main' => $contact->main]);
                $phone->save();
                return $this->data($phone);
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) { throw new PhoneContactConflict(); }
            throw $exception;
        }
    }

    public function removeForSession(PhoneContactSession $session, int $phoneId): void
    {
        DB::transaction(function () use ($session, $phoneId): void {
            $this->lockActiveSession($session);
            $phones = Phone::where('user_id', $session->actorId)->orderBy('id')->lockForUpdate()->get();
            $phone = $phones->firstWhere('id', $phoneId);
            if ($phone === null) { return; }
            $phone->delete();
            if ($phone->main) {
                $remaining = $phones->first(fn (Phone $candidate): bool => $candidate->id !== $phoneId);
                if ($remaining !== null) { $remaining->forceFill(['main' => true])->save(); }
            }
        });
    }

    private function lockActiveSession(PhoneContactSession $session): void
    {
        $user = User::query()->lockForUpdate()->find($session->actorId);
        $active = DB::table('sessions')->where('user_id', $session->actorId)->where('id', $session->sessionId())->lockForUpdate()->first(['id']);
        if ($user === null || $active === null) { throw new InactiveSession(); }
    }

    public function changeForSession(PhoneContactSession $session, int $phoneId, \App\Modules\Identity\Domain\PhoneContactChange $change): PhoneContactData
    {
        return DB::transaction(function () use ($session, $phoneId, $change): PhoneContactData {
            $this->lockActiveSession($session);
            $phones = Phone::where('user_id', $session->actorId)->orderBy('id')->lockForUpdate()->get();
            $phone = $phones->firstWhere('id', $phoneId);
            if ($phone === null) { throw new \App\Modules\Identity\Application\PhoneContactNotFound(); }
            $change->assertApplicableTo($phone->main);
            if ($change->main === true) {
                foreach ($phones as $other) {
                    if ($other->id !== $phoneId && $other->main) { $other->forceFill(['main' => false])->save(); }
                }
            }
            if ($change->label !== null) { $phone->forceFill(['label' => $change->label->value]); }
            if ($change->main !== null) { $phone->forceFill(['main' => $change->main]); }
            $phone->save();
            return $this->data($phone);
        });
    }

    private function data(Phone $phone): PhoneContactData
    {
        return new PhoneContactData($phone->id, $phone->label, $phone->country_code, $phone->number->value, $phone->main);
    }
}

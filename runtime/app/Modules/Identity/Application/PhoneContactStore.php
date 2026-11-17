<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\PhoneContact;

interface PhoneContactStore
{
    /** @return list<PhoneContactData> */
    public function listForSession(PhoneContactSession $session): array;
    public function addForSession(PhoneContactSession $session, PhoneContact $contact): PhoneContactData;
    public function removeForSession(PhoneContactSession $session, int $phoneId): void;
}

<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\PhoneContact;
use App\Modules\Identity\Domain\PhoneContactChange;

interface PhoneContactStore
{
    /** @return list<PhoneContactData> */
    public function listForSession(PhoneContactSession $session): array;
    public function addForSession(PhoneContactSession $session, PhoneContact $contact): PhoneContactData;
    public function removeForSession(PhoneContactSession $session, int $phoneId): void;
    public function changeForSession(PhoneContactSession $session, int $phoneId, PhoneContactChange $change): PhoneContactData;
}

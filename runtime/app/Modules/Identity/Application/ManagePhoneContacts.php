<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\PhoneContact;

final readonly class ManagePhoneContacts
{
    public function __construct(private PhoneContactStore $contacts) {}

    /** @return list<PhoneContactData> */
    public function listing(PhoneContactSession $session): array
    {
        return $this->contacts->listForSession($session);
    }

    public function add(PhoneContactSession $session, string $label, string $countryCode, string $number, bool $main): PhoneContactData
    {
        return $this->contacts->addForSession($session, PhoneContact::fromStrings($label, $countryCode, $number, $main));
    }

    public function remove(PhoneContactSession $session, int $phoneId): void
    {
        if ($phoneId < 1) { throw new \InvalidArgumentException('Invalid phone identifier.'); }
        $this->contacts->removeForSession($session, $phoneId);
    }
}

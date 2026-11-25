<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\PhoneContact;

final readonly class ManagePhoneContacts
{
    public function __construct(private PhoneContactStore $contacts, private MobilePhoneValidation $mobileNumbers) {}

    /** @return list<PhoneContactData> */
    public function listing(PhoneContactSession $session): array
    {
        return $this->contacts->listForSession($session);
    }

    public function add(PhoneContactSession $session, string $label, string $countryCode, string $number, bool $main): PhoneContactData
    {
        $contact = PhoneContact::fromStrings($label, $countryCode, $number, $main);
        if (!$this->mobileNumbers->accepts($contact->number, $contact->countryCode)) {
            throw new \InvalidArgumentException('Invalid mobile phone contact.');
        }
        return $this->contacts->addForSession($session, $contact);
    }

    public function remove(PhoneContactSession $session, int $phoneId): void
    {
        if ($phoneId < 1) { throw new \InvalidArgumentException('Invalid phone identifier.'); }
        $this->contacts->removeForSession($session, $phoneId);
    }

    public function change(PhoneContactSession $session, int $phoneId, ?string $label, ?bool $main): PhoneContactData
    {
        if ($phoneId < 1) { throw new \InvalidArgumentException('Invalid phone identifier.'); }
        return $this->contacts->changeForSession($session, $phoneId, \App\Modules\Identity\Domain\PhoneContactChange::fromValues($label, $main));
    }
}

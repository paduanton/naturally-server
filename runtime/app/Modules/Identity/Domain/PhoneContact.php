<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

/** Canonical storage values; mobile validity and country agreement belong to the input adapter. */
final readonly class PhoneContact
{
    private function __construct(public string $label, public string $countryCode, public PhoneNumber $number, public bool $main) {}

    public static function fromStrings(string $label, string $countryCode, string $number, bool $main): self
    {
        $label = PhoneLabel::fromString($label)->value;
        $countryCode = strtoupper(trim($countryCode));
        if (preg_match('/\A[A-Z]{2}\z/D', $countryCode) !== 1) {
            throw new \InvalidArgumentException('Invalid phone contact details.');
        }
        return new self($label, $countryCode, PhoneNumber::fromString($number), $main);
    }

    public function assertFitsCollection(int $activeCount, bool $hasMain): void
    {
        if ($activeCount >= 2 || (!$hasMain && !$this->main) || ($hasMain && $this->main)) {
            throw new PhoneContactConflict();
        }
    }
}

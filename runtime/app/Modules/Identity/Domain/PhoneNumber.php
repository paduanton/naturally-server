<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

/** Canonical notation only; country/mobile validity is checked at the input boundary. */
final readonly class PhoneNumber implements \Stringable
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        if (preg_match('/\A[+][1-9][0-9]{1,14}\z/D', $value) !== 1) {
            throw new \InvalidArgumentException('Phone number must use canonical international notation.');
        }
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

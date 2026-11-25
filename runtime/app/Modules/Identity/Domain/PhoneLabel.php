<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final readonly class PhoneLabel
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        $value = trim($value);
        if (mb_strlen($value) < 4 || mb_strlen($value) > 15 || preg_match('/[\p{C}]/u', $value) !== 0) {
            throw new \InvalidArgumentException('Invalid phone label.');
        }
        return new self($value);
    }
}

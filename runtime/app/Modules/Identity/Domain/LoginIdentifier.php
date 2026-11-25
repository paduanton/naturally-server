<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final readonly class LoginIdentifier
{
    private function __construct(public string $column, public string $value) {}

    public static function fromString(string $value): self
    {
        return str_contains($value, '@')
            ? new self('email', EmailAddress::fromString($value)->value)
            : new self('username', Username::fromString($value)->value);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final readonly class EmailAddress
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));
        if (strlen($value) > 255 || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Invalid email address.');
        }
        return new self($value);
    }
}

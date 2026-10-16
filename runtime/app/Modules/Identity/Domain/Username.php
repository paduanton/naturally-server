<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final readonly class Username
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));
        if (strlen($value) < 4 || strlen($value) > 20 || preg_match('/\A[a-z0-9]+(?:[._][a-z0-9]+)?\z/D', $value) !== 1) {
            throw new \InvalidArgumentException('Invalid username.');
        }
        return new self($value);
    }
}

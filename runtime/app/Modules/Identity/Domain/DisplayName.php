<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final readonly class DisplayName
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        $value = trim($value);
        if (!mb_check_encoding($value, 'UTF-8') || mb_strlen($value, 'UTF-8') < 1 || mb_strlen($value, 'UTF-8') > 255 || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw new \InvalidArgumentException('Invalid display name.');
        }
        return new self($value);
    }
}

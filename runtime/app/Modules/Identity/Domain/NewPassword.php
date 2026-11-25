<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final readonly class NewPassword
{
    private function __construct(#[\SensitiveParameter] private string $value) {}

    public static function fromString(#[\SensitiveParameter] string $value): self
    {
        if (!mb_check_encoding($value, 'UTF-8') || mb_strlen($value, 'UTF-8') < 15 || mb_strlen($value, 'UTF-8') > 1024) {
            throw new \InvalidArgumentException('Password must contain between 15 and 1024 characters.');
        }
        return new self($value);
    }

    public function reveal(): string { return $this->value; }

    public function __debugInfo(): array { return ['value' => '[REDACTED]']; }

    public function __serialize(): array { throw new \LogicException('Credentials cannot be serialized.'); }
}

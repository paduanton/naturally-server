<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final readonly class RecoveryToken
{
    private function __construct(#[\SensitiveParameter] private string $value) {}

    public static function fromString(#[\SensitiveParameter] string $value): self
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/D', $value)) { throw new \InvalidArgumentException('Invalid recovery proof.'); }
        return new self($value);
    }

    public function reveal(): string { return $this->value; }
    public function __debugInfo(): array { return ['value' => '[REDACTED]']; }
    public function __serialize(): array { throw new \LogicException('Credentials cannot be serialized.'); }
}

<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class PasswordResetInput
{
    public function __construct(public string $email, #[\SensitiveParameter] private string $token, #[\SensitiveParameter] private string $password) {}
    public function token(): string { return $this->token; }
    public function password(): string { return $this->password; }
    public function __debugInfo(): array { return ['credentials' => '[REDACTED]']; }
    public function __serialize(): array { throw new \LogicException('Credentials cannot be serialized.'); }
}

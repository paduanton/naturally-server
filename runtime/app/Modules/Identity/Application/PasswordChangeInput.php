<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class PasswordChangeInput
{
    public function __construct(public int $actorId, #[\SensitiveParameter] private string $sessionId, #[\SensitiveParameter] private string $password) {}
    public function sessionId(): string { return $this->sessionId; }
    public function password(): string { return $this->password; }
    public function __debugInfo(): array { return ['credentials' => '[REDACTED]']; }
    public function __serialize(): array { throw new \LogicException('Credentials cannot be serialized.'); }
}

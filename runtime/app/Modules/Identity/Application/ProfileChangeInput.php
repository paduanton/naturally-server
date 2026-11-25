<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class ProfileChangeInput
{
    public function __construct(public int $actorId, #[\SensitiveParameter] private string $sessionId, public ?string $name, public ?string $username, public ?string $birthday = null, public bool $birthdayProvided = false) {}
    public function sessionId(): string { return $this->sessionId; }
    public function __debugInfo(): array { return ['session' => '[REDACTED]']; }
    public function __serialize(): array { throw new \LogicException('Session credentials cannot be serialized.'); }
}

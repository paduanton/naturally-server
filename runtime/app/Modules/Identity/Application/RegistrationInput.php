<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final readonly class RegistrationInput
{
    public function __construct(public string $name, public string $username, public string $email, #[\SensitiveParameter] private string $password) {}
    public function password(): string { return $this->password; }
    public function __debugInfo(): array { return ['credentials' => '[REDACTED]']; }
    public function __serialize(): array { throw new \LogicException('Credentials cannot be serialized.'); }
}

<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final readonly class SocialIdentity
{
    private function __construct(
        public SocialProvider $provider,
        #[\SensitiveParameter] private string $subject,
        #[\SensitiveParameter] private ?EmailAddress $email,
        public bool $emailVerified,
    ) {}

    /** The adapter establishes email authority after verifying the provider response. */
    public static function fromProvider(
        SocialProvider $provider,
        #[\SensitiveParameter] string $subject,
        #[\SensitiveParameter] ?string $email,
        bool $authoritativeEmail,
    ): self {
        if ($subject === '' || trim($subject) !== $subject
            || mb_strlen($subject, 'UTF-8') > 191 || preg_match('/\p{C}/u', $subject) !== 0) {
            throw new \InvalidArgumentException('Invalid provider identity.');
        }

        $address = $email === null ? null : EmailAddress::fromString($email);
        $verified = $provider === SocialProvider::Google && $address !== null && $authoritativeEmail;

        return new self($provider, $subject, $address, $verified);
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function email(): ?EmailAddress
    {
        return $this->email;
    }

    public function __debugInfo(): array
    {
        return ['identity' => '[REDACTED]'];
    }

    public function __serialize(): array
    {
        throw new \LogicException('Provider identities cannot be serialized.');
    }
}

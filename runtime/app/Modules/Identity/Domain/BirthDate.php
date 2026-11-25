<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final readonly class BirthDate
{
    private function __construct(public string $value, private \DateTimeImmutable $date) {}

    public static function fromString(string $value): self
    {
        if (preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/D', $value) !== 1) { throw new \InvalidArgumentException('Invalid birth date.'); }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));
        if ($date === false || $date->format('Y-m-d') !== $value || $value <= '1920-01-01') { throw new \InvalidArgumentException('Invalid birth date.'); }
        return new self($value, $date);
    }

    public function assertAllowedOn(\DateTimeImmutable $today): void
    {
        $today = $today->setTimezone(new \DateTimeZone('UTC'))->setTime(0, 0);
        if ($this->date > $today || $this->date->diff($today)->y < 7) { throw new \InvalidArgumentException('Invalid birth date.'); }
    }
}

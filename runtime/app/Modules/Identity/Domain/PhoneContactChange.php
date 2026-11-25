<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

final readonly class PhoneContactChange
{
    private function __construct(public ?PhoneLabel $label, public ?bool $main) {}

    public static function fromValues(?string $label, ?bool $main): self
    {
        if ($label === null && $main === null) { throw new \InvalidArgumentException('No phone changes supplied.'); }
        return new self($label === null ? null : PhoneLabel::fromString($label), $main);
    }

    public function assertApplicableTo(bool $currentlyMain): void
    {
        if ($currentlyMain && $this->main === false) { throw new \DomainException('A primary phone must remain.'); }
    }
}

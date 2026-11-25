<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Database\Casts;

use App\Modules\Identity\Domain\PhoneNumber;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** @implements CastsAttributes<PhoneNumber, PhoneNumber|string> */
final class PhoneNumberCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): PhoneNumber
    {
        if (!is_string($value)) {
            throw new \UnexpectedValueException('Stored phone value has an invalid representation.');
        }
        return PhoneNumber::fromString($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof PhoneNumber) {
            return $value->value;
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Phone value must be a canonical string or PhoneNumber.');
        }
        return PhoneNumber::fromString($value)->value;
    }
}

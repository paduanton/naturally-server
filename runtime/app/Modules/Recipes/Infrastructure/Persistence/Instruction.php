<?php

declare(strict_types=1);

namespace App\Modules\Recipes\Infrastructure\Persistence;

use App\Modules\Recipes\Infrastructure\Database\Factories\InstructionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Instruction extends Model
{
    /** @use HasFactory<InstructionFactory> */
    use HasFactory;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['recipe_id' => 'integer', 'position' => 'integer'];
    }

    protected static function newFactory(): InstructionFactory
    {
        return InstructionFactory::new();
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}

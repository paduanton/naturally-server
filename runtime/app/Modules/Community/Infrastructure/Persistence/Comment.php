<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure\Persistence;

use App\Modules\Community\Infrastructure\Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'comments';
    protected $guarded = ['*'];
    protected $hidden = [];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'recipe_id' => 'integer', 'parent_id' => 'integer'];
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('id');
    }
}

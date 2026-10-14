<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure\Persistence;

use App\Modules\Community\Infrastructure\Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'reports';
    protected $guarded = ['*'];
    protected $hidden = ['email', 'reporter_name'];

    protected function casts(): array
    {
        return ['user_id' => 'integer'];
    }

    protected static function newFactory(): ReportFactory
    {
        return ReportFactory::new();
    }

}

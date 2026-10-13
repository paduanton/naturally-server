<?php

declare(strict_types=1);

use App\Shared\Infrastructure\MysqlChecks;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phones', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('main');
            $table->string('label', 15);
            $table->char('country_code', 2)->collation('utf8mb4_bin');
            $table->string('number', 16)->collation('utf8mb4_bin')->unique('phone_number_unique');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedTinyInteger('primary_slot')->nullable()->virtualAs('CASE WHEN main = 1 AND deleted_at IS NULL THEN 1 ELSE NULL END');
            $table->unique(['user_id', 'primary_slot'], 'phone_primary_unique');
        });
        MysqlChecks::add(Schema::getConnection(), 'phones', 'phone_format', "REGEXP_LIKE(number, '^[+][1-9][0-9]{1,14}$', 'c') AND NOT REGEXP_LIKE(number, '[^+0-9]', 'c') AND REGEXP_LIKE(country_code, '^[A-Z]{2}$', 'c') AND main IN (0, 1)");
    }

    public function down(): void
    {
        Schema::dropIfExists('phones');
    }
};

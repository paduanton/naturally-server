<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure\Database\Factories;

use App\Modules\Community\Infrastructure\Persistence\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Report> */
final class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return ['user_id' => null, 'title' => 'Synthetic report', 'description' => 'Synthetic report details', 'category' => 'bug', 'email' => null, 'reporter_name' => null];
    }

    public function forReporter(int $userId): static
    {
        return $this->state(['user_id' => $userId]);
    }
}

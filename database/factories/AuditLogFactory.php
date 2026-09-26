<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_name' => fake()->name(),
            'period_title' => 'JULI-AGUSTUS 2026',
            'field' => 'tahfidz_notes',
            'old_value' => null,
            'new_value' => fake()->sentence(),
        ];
    }
}

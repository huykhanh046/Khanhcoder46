<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => strtoupper(fake()->unique()->bothify('CODE????')), 'type' => 'percent', 'value' => 1000, 'min_order' => 0, 'usage_limit' => 10, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'active' => true];
    }
}

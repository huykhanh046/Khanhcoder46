<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopFactory extends Factory
{
    public function definition(): array
    {
        return ['owner_id' => User::factory(), 'name' => fake()->company(), 'slug' => fake()->unique()->slug(), 'status' => 'approved'];
    }
}

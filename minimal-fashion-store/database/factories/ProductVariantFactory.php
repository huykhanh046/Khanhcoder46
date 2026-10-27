<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'sku' => fake()->unique()->uuid(), 'size' => 'M', 'color' => 'Đen', 'price' => 200000, 'active' => true];
    }
}

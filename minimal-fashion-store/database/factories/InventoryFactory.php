<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    public function definition(): array
    {
        return ['product_variant_id' => ProductVariant::factory(), 'stock' => 10, 'reserved' => 0];
    }
}

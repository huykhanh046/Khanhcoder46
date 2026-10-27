<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return ['shop_id' => Shop::factory(), 'category_id' => Category::factory(), 'name' => 'Áo cotton mẫu', 'slug' => fake()->unique()->slug(), 'description' => 'Mô tả sản phẩm', 'status' => 'approved', 'active' => true];
    }
}

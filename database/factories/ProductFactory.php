<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Product::class;

    public function definition()
    {
        $name = $this->faker->words(2, true);
        return [
            'name' => $name,
            'barcode' => $this->faker->unique()->numerify('BC###-#####'),
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numerify('###'),
        ];
    }
}

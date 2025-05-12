<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->words(3, true);
        
        return [
            'user_id' => User::where('role', 'vendeur')->inRandomOrder()->first()->id,
            'category_id' => Category::inRandomOrder()->first()->id,
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . uniqid(),
            'description' => $this->faker->paragraph(3),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock' => $this->faker->numberBetween(0, 100),
            'stock_threshold' => $this->faker->numberBetween(5, 20),
            'active' => true,
            'image' => null, // Dans un environnement réel, vous pourriez utiliser faker pour une image
        ];
    }
};
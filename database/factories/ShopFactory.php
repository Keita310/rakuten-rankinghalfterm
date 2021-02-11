<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Shop::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $shopCode = $this->faker->unique()->word;
        return [
            'shop_code' => $shopCode,
            'shop_url' => "https://www.rakuten.co.jp/{$shopCode}/",
            'shop_compony' => $this->faker->unique()->company,
            'shop_name' => $this->faker->unique()->country . '楽天市場店',
            'shop_mail' => "{$shopCode}@shop.rakuten.co.jp",
        ];
    }
}

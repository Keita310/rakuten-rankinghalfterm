<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Shop;
use App\Models\Items;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemsFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Items::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $shop = Shop::all()->random();
        $code = $this->faker->unique()->lexify();
        $image = "https://shop.r10s.jp/{$shop->shop_code}/cabinet/{$this->faker->unique()->word}.jpg";
        $url = "https://item.rakuten.co.jp/{$shop->shop_code}/{$code}/";
        $reviewCount = (int)$this->faker->numberBetween(0, 999);
        $reviewAverage = ($reviewCount > 0) ? $this->faker->randomFloat(2, 1, 5) : 0;

        return [
            'cate_id' => Category::all()->pluck('id')->random(),
            'shop_id' => $shop->id,
            'rank' => $this->faker->numberBetween(1, 30),
            'code' => $code,
            'name' => $this->faker->unique()->realText(50),
            'catchcopy' => $this->faker->unique()->realText(100),
            'price' => $this->faker->numberBetween(100, 99999),
            'image' => $image,
            'url' => $url,
            'point_rate' => $this->faker->numberBetween(1, 10),
            'review_count' => $reviewCount,
            'review_average' => $reviewAverage,
            'soldout' => $this->faker->numberBetween(0, 1),
            'asuraku_flag' => $this->faker->numberBetween(0, 1),
            'postage_flag' => 1,
            'availability' => 1,
        ];
    }
}

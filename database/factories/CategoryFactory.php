<?php

namespace Database\Factories;

use Carbon\Carbon;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $now = Carbon::now();
        $nowYear = (int)$now->year;
        $year = $this->faker->randomElement([$nowYear - 1,  $nowYear - 2]);
        $season = $this->faker->randomElement(['first', 'last']);
        if ($season === 'first') {
            $catePeriodStart = ($year - 1) . '-11-01 00:00:00';
            $catePeriodEnd = $year . '-04-30 00:00:00';
        } else {
            $catePeriodStart = $year . '-05-01 00:00:00';
            $catePeriodEnd = $year . '-10-31 00:00:00';
        }
        $cateSeason = "{$year}_{$season}";
        $cateCode = $this->faker->unique()->word;

        return [
            'cate_code' => $cateCode,
            'cate_season' => $cateSeason,
            'cate_url' => "https://event.rakuten.co.jp/rankinghalfterm/{$cateCode}/",
            'cate_name' =>  "{$this->faker->unique()->city}・{$this->faker->unique()->city}",
            'cate_period_start' => $catePeriodStart,
            'cate_period_end' => $catePeriodEnd,
        ];
    }
}

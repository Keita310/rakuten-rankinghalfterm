<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Shop;
use App\Models\Items;

// test target
use App\Http\Controllers\Api\RankinghalftermController;

// ./vendor/bin/phpunit tests/Feature/Api/RankinghalftermControllerTest.php

class RankinghalftermControllerTest extends TestCase
{

    // DB初期化のトランザクション
    use DatabaseTransactions;

    // 対象期間
    private $cateSeason;

    public function setUp(): void
    {
        parent::setUp();

        // データベースマイグレーション
        $this->artisan('migrate');
        // データ挿入
        $this->seed('CategoriesTableSeeder');
        $this->seed('ShopsTableSeeder');
        $this->seed('ItemsTableSeeder');

        // 対象期間セット
        $now = Carbon::now();
        $month = (int)$now->month;
        $this->cateSeason = $now->subYear()->year . '_' . (($month > 4 && $month < 11) ? 'last' : 'first');
    }

    /**
     * 正常系
     * (カテゴリ毎全取得)
     */
    public function testIndex_カテゴリ毎全取得()
    {
        $response = $this->json(
            'GET',
            '/api/ranking',
            [
                'type' => 'cate',
                'cate_season' => $this->cateSeason,
            ]
        );
        // レスポンスの検証
        $response->assertOk();

        $data = $response->baseResponse->getData()->data;
        $data = json_decode(json_encode($data), true); // 配列型に変換

        // cate_seasonは一種のみ
        $cateSeasons = array_column($data, 'cate_season');
        $this->assertEquals(1, count(array_unique($cateSeasons)));
    }

    /**
     * 正常系
     * (ショップ毎全取得)
     */

    public function testIndex_ショップ毎全取得()
    {
        $response = $this->json(
            'GET',
            '/api/ranking',
            [
                'type' => 'shop',
                'cate_season' => $this->cateSeason,
            ]
        );
        // レスポンスの検証
        $response->assertOk();

        $data = $response->baseResponse->getData()->data;
        $data = json_decode(json_encode($data), true); // 配列型に変換

        // cate_seasonは一種のみ
        $cateSeasons = [];
        foreach ($data as $shop) {
            foreach ($shop['items'] as $item) {
                $cateSeasons[] = $item['category']['cate_season'];
            }
        }
        $this->assertEquals(1, count(array_unique($cateSeasons)));
    }

    /**
     * エラー系
     * (パラメータ不正)
     */
    public function testIndex_パラメータ不正()
    {
        $response = $this->json(
            'GET',
            '/api/ranking',
            [
                'type' => 'xxx',
                'cate_season' => 'xxx',
            ]
        );
        $errors = $response->baseResponse->getData()->errors;

        // 422で返る
        self::assertEquals(422, $response->baseResponse->getStatusCode());
        // エラーメッセージ
        self::assertEquals('The selected type is invalid.', $errors->type[0]);
        self::assertEquals('The cate season format is invalid.', $errors->cate_season[0]);
    }

}
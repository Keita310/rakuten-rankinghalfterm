<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Shop;
use App\Models\Items;
use App\Actions\RakutenItemUtil;
use App\Actions\StringUtil;
use KubAT\PhpSimple\HtmlDomParser;

class CreateRankingDataCommand extends Command
{
    // 半期ランキングのメインURL
    const MAIN_PAGE = 'https://event.rakuten.co.jp/rankinghalfterm/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:create_ranking_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '楽天半期ランキングデータ集計コマンド';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 対象ページを取得
        $urls = self::getUrls();
        foreach ($urls as $url) {
            $this->getItemData($url);
        }

        // サービスクラスに移植する

        // apiでは指定したパラメータでDBから取得できるようにする
        dd(11);
    }

    /**
     * 対象ページから商品データを取得
     * @return array
     */
    private function getItemData($url)
    {
        $existShops = [];

        $dom = HtmlDomParser::file_get_html($url);

        $cateId = self::toCateId($url);
        $titles = explode('｜', $dom->find('title', 0)->innertext);
        list($start, $end) = self::splitPeriod($dom);
        Category::updateOrCreate(
            [
                'cate_id' => $cateId
            ],
            [
                'cate_url' => $url,
                'cate_name' => $titles[1],
                'cate_season' => str_replace('【楽天市場】', '', $titles[0]),
                'cate_period_start' => $start,
                'cate_period_end' => $end,
            ]
        );

        $rankDoms = $dom->find('.itemList');
        foreach ($rankDoms as $rankDom) {
            $url = $rankDom->find('a', 0)->href;
            $shopId = StringUtil::toShopId($url);
            $existShops[] = $shopId;

            // 同じショップは処理しない
            if (!in_array($shopId, $existShops, true)) {
                Shop::updateOrCreateByShopId($shopId);
            }

            $item = RakutenItemUtil::getByItemUrl($url);
            $item['rank'] = (int)$rankDom->find('.rankIcon span', 0)->innertext;

            Items::updateOrCreate(
                [
                    'shop_id' => $shopId,
                    'cate_id' => $cateId,
                    'code' => $item['code'],
                ],
                $item
            );
        }
    }

    /**
     * 集計期間を分離
     * @param $dom
     * @return array
     */
    private static function splitPeriod($dom)
    {
        $period = $dom->find('.cmnSummaryWrap dd', 0)->innertext;
        $period = str_replace(['年', '月', '日'], ['-', '-', ''], $period);
        $period = preg_replace('/（.*?）/', '', $period);
        return explode('～', $period);
    }

    /**
     * URLからカテゴリIDを取得
     * @param $url
     * @return array
     */
    private static function toCateId($url)
    {
        return preg_replace('/(^.*\/)(.*?)(\/$)/', '$2', $url);
    }

    /**
     * スクレイピングするページ(URL)をメインページから取得
     * @return array
     */
    private static function getUrls()
    {
        $urls = [];
        $dom = HtmlDomParser::file_get_html(self::MAIN_PAGE);
        $links = $dom->find('a[href^="' . self::MAIN_PAGE . '"]');
        foreach ($links as $link) {
            // メインページは除く
            if ($link->href === self::MAIN_PAGE) {
                continue;
            }
            $urls[] = $link->href;
        }
        $dom->clear();

        return array_unique(array_values($urls));
    }
}

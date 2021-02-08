<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Shop;
use App\Models\Items;
use App\Actions\RakutenItemUtil;
use App\Actions\StringUtil;
use KubAT\PhpSimple\HtmlDomParser;

/**
 * 半期ランキングデータ収集サービス
 */
class CreateRankingDataService
{
    // 半期ランキングのメインURL
    const MAIN_PAGE = 'https://event.rakuten.co.jp/rankinghalfterm/';

    /**
     * メイン処理
     * @return void
     */
    public function handle()
    {
        // 対象ページを取得
        $urls = self::getUrls();
        $flag = false;
        foreach ($urls as $url) {
            $this->getItemData($url);
        }
    }

    /**
     * 対象ページから商品データを取得
     * @return array
     */
    private function getItemData($url)
    {
        $dom = HtmlDomParser::file_get_html($url);

        $cateCode = self::toCateCode($url);
        $titles = explode('｜', $dom->find('title', 0)->innertext);
        $cateSeason = $titles[0];
        $half = (strpos($titles[0], '上半期') !== false) ? 'first' : 'last';
        $cateSeason = preg_replace('/[^0-9]/', '', $cateSeason) . "_{$half}";
        list($start, $end) = self::splitPeriod($dom);

        $category = Category::updateOrCreate(
            [
                'cate_code' => $cateCode,
                'cate_season' => $cateSeason,
            ],
            [
                'cate_url' => $url,
                'cate_name' => $titles[1],
                'cate_period_start' => $start,
                'cate_period_end' => $end,
            ]
        );

        $rankDoms = $dom->find('.itemList');
        foreach ($rankDoms as $rankDom) {
            $url = $rankDom->find('a', 0)->href;
            $shopCode = StringUtil::toShopCode($url);

            $item = RakutenItemUtil::getByItemUrl($url);
            if (!$item) {
                continue;
            }

            $shop = Shop::updateOrCreateByShopCode($shopCode);

            $item['rank'] = (int)$rankDom->find('.rankIcon span', 0)->innertext;
            Items::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'cate_id' => $category->id,
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
     * URLからカテゴリCodeを取得
     * @param $url
     * @return array
     */
    private static function toCateCode($url)
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
            // メインページ、特別賞は除く
            if ($link->href === self::MAIN_PAGE || strpos($link->href, '/special/') !== false) {
                continue;
            }
            $urls[] = $link->href;
        }
        $dom->clear();

        return array_unique(array_values($urls));
    }
}

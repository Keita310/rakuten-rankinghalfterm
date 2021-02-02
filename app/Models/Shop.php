<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Actions\ScrapingUtil;
use KubAT\PhpSimple\HtmlDomParser;

class Shop extends Model
{
    protected $guarded = array('id');
    protected $fillable = [];

    /**
     * shopIdを元に会社名等の情報をupdateOrCreateする
     * @param $url
     * @return array
     */
    public static function updateOrCreateByShopId($shopId)
    {
        // 楽天ブックス用
        if ($shopId === 'book') {
            self::updateOrCreateByBooks();
            return;
        }

        $shopUrl = "https://www.rakuten.co.jp/{$shopId}/";
        $infoUrl = "{$shopUrl}info.html";
        $content = ScrapingUtil::get($infoUrl, 'sp');
        $dom = HtmlDomParser::str_get_html($content);

        $shopName = $dom->find('title', 0)->innertext;
        $shopName = preg_replace('/( 【楽天市場】)(.*?)( \[会社概要\] )/', '$2', $shopName);
        $shopMail = $dom->find('a[href^="mailto"]', 0)->href;
        $shopMail = str_replace('mailto:', '', $shopMail);
        $shopMail = urldecode($shopMail);

        self::updateOrCreate(
            [
                'shop_id' => $shopId
            ],
            [
                'shop_url' => $shopUrl,
                'shop_compony' => $dom->find('.c-spCompanyName', 0)->innertext,
                'shop_name' => $shopName,
                'shop_mail' => $shopMail,
            ]
        );
        sleep(1);
    }

    /**
     * 楽天ブックスだったら固定データ挿入
     * @return void
     */
    private static function updateOrCreateByBooks()
    {
        self::updateOrCreate(
            [
                'shop_id' => 'book'
            ],
            [
                'shop_url' => 'https://books.rakuten.co.jp/',
                'shop_compony' => '楽天株式会社',
                'shop_name' => '楽天ブックス',
                'shop_mail' => null,
            ]
        );
    }
}
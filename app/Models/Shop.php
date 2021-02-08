<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Actions\ScrapingUtil;
use KubAT\PhpSimple\HtmlDomParser;
use App\Models\Items;

class Shop extends Model
{
    protected $guarded = array('id');
    protected $fillable = [];

    /**
     * itemsテーブルとのリレーション
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function items()
    {
        return $this->hasMany(Items::class, 'shop_id');
    }

    /**
     * ショップ毎で抽出する
     * @return array
     */
    public static function get($cateSeason, $shopCode = null)
    {
        $where = ($shopCode) ? ['shop_code' => $shopCode] : [];
        $itemIds = Items::whereHas('category', function($query) use ($cateSeason) {
                $query->where(['cate_season' => $cateSeason]);
            })
            ->pluck('id');
        return self::where($where)
            ->whereHas('items', function($query) use ($itemIds) {
                $query->whereIn('id', $itemIds);
            })
            ->with(['items', 'items.category'])
            ->get();
    }

    /**
     * shopCodeを元に会社名等の情報をupdateOrCreateする
     * @param $url
     * @return array
     */
    public static function updateOrCreateByShopCode($shopCode)
    {
        // データが存在したら更新しないで返す
        $shop = self::where(['shop_code' => $shopCode])->first();
        if ($shop) {
            return $shop;
        }

        sleep(1);

        // 楽天ブックス用
        if ($shopCode === 'book') {
            return self::updateOrCreateByBooks();
        }

        // 楽天ファッション用
        if ($shopCode === 'stylife') {
            return self::updateOrCreateByFashion();
        }

        $shopUrl = "https://www.rakuten.co.jp/{$shopCode}/";
        $infoUrl = "{$shopUrl}info.html";
        $content = ScrapingUtil::get($infoUrl, 'sp');
        $dom = HtmlDomParser::str_get_html($content);

        $shopName = $dom->find('title', 0)->innertext;
        $shopName = preg_replace('/( 【楽天市場】)(.*?)( \[会社概要\] )/', '$2', $shopName);
        $mailto = $dom->find('a[href^="mailto"]', 0);
        if ($mailto) {
            $mailto = $dom->find('a[href^="mailto"]', 1);
        }
        if ($mailto) {
            $shopMail = $mailto->href;
            $shopMail = str_replace('mailto:', '', $shopMail);
            $shopMail = preg_replace('/(^.*?)(\?).*$/', '$1', $shopMail);
            $shopMail = urldecode($shopMail);
            $shopMail = preg_replace('/(^.*?)(,).*$/', '$1', $shopMail);
        } else {
            $shopMail = null;
        }
        if ($dom->find('.c-spCompanyName', 0)) {
            $shopCompony = $dom->find('.c-spCompanyName', 0)->innertext;
        } else {
            $shopCompony = $shopName;
        }

        return self::updateOrCreate(
            [
                'shop_code' => $shopCode
            ],
            [
                'shop_url' => $shopUrl,
                'shop_compony' => $shopCompony,
                'shop_name' => $shopName,
                'shop_mail' => $shopMail,
            ]
        );
    }

    /**
     * 楽天ブックスだったら固定データ挿入
     * @return void
     */
    private static function updateOrCreateByBooks()
    {
        return self::updateOrCreate(
            [
                'shop_code' => 'book'
            ],
            [
                'shop_url' => 'https://books.rakuten.co.jp/',
                'shop_compony' => '楽天株式会社',
                'shop_name' => '楽天ブックス',
                'shop_mail' => null,
            ]
        );
    }

    /**
     * 楽天ファッションだったら固定データ挿入
     * @return void
     */
    private static function updateOrCreateByFashion()
    {
        return self::updateOrCreate(
            [
                'shop_code' => 'stylife'
            ],
            [
                'shop_url' => 'https://brandavenue.rakuten.co.jp/',
                'shop_compony' => '楽天株式会社',
                'shop_name' => 'RakutenFashion',
                'shop_mail' => null,
            ]
        );
    }

}
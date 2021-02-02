<?php

namespace App\Actions;

use App\Actions\ScrapingUtil;
use App\Actions\StringUtil;
use KubAT\PhpSimple\HtmlDomParser;

/**
 * Class RakutenItemUtil
 * 楽天商品データ関連のユーティリティ
 *
 * @package App\Actions
 */
class RakutenItemUtil
{
    // 商品データフォーマット
    const FORMAT = [
        'code',
        'name',
        'catchcopy',
        'price',
        'image',
        'url',
        'point_rate',
        'review_count',
        'review_average',
        'soldout',
        'asuraku_flag',
        'postage_flag',
        'availability',
    ];

    /**
     * 商品商品URLで検索
     * 楽天の商品ページをスクレイピングする
     * 1件の商品情報を返す
     * @param $url
     * @return array
     */
    public static function getByItemUrl($url)
    {
        sleep(1);
        // 楽天商品URL以外の場合抜ける
        if (!self::isItemUrl($url)) {
            return [];
        }
        if (self::isBookUrl($url)) {
            return self::getBookPageByItemUrl($url);
        }
        return self::getDefaultPageByItemUrl($url);
    }

    /**
     * 通常ページの商品データ取得
     * @param $url
     * @return array
     */
    private static function getDefaultPageByItemUrl($url)
    {
        $content = ScrapingUtil::get($url);
        $content = preg_replace('/\r|\n/', '', $content);
        if (!$content) {
            return [];
        }

        $dom = HtmlDomParser::str_get_html($content);
        $params1 = self::getParams1($content);
        $params2 = self::getParams2($dom);
        $code = StringUtil::toItemCode($url);
        $index = self::cartIndex($content, $code);
        $cartDom = self::cartDom($dom, $index);

        $reviewCount = (int)$params2['irevnum'][$index];
        $reviewAverage = self::reviewAverage($reviewCount, $params1, $index);

        $item = array_combine(self::FORMAT, [
            $code,
            ($dom->find('.item_name', $index)) ? $dom->find('.item_name', $index)->plaintext : '',
            ($dom->find('.catch_copy', $index)) ? $dom->find('.catch_copy', $index)->plaintext : '',
            (int)$params1['price'][$index],
            urldecode($params1['imgurl'][$index]),
            $url,
            self::pointRate($cartDom),
            $reviewCount,
            $reviewAverage,
            ($dom->find('#rakutenLimitedId_aroundCart', $index)->find('.soldout_msg', 0)) ? true : false,
            $params2['asuraku_item_flg'][$index],
            ($cartDom->find('.shippingCost_free', 0)) ? '0' : '1',
            1,//販売可能商品とする
        ]);

        $dom->clear();
        return $item;
    }

    /**
     * 楽天ブックページの商品データ取得
     * @param $url
     * @return array
     */
    private static function getBookPageByItemUrl($url)
    {
        // 楽天BOOKのURLを新フォーマットに
        $url = self::newBoolUrl($url);
        $content = ScrapingUtil::get($url);
        // 取得できなかったらパラメータをつけてみる
        if (!$content) {
            $content = ScrapingUtil::get($url . '?bkts=1');
        }
        if (!$content) {
            return [];
        }
        $content = preg_replace('/\r|\n/', '', $content);

        $dom = HtmlDomParser::str_get_html($content);
        $stock = $dom->find('#ratCustomParameters', 0)->value;
        $stock = (strpos($stock, 'instock') === false);
        $reviewCount = $dom->find('[itemprop="reviewCount"]', 0)->innertext;
        $reviewCount = (int)str_replace(',', '', $reviewCount);

        $item = array_combine(self::FORMAT, [
            $dom->find('#ratItemManageNo', 0)->value,
            $dom->find('#ratItemName', 0)->value,
            null,
            (int)$dom->find('#ratItemPrice', 0)->value,
            'https:' . $dom->find('.main-js-slick__item a', 0)->href,
            $url,
            1,
            $reviewCount,
            $dom->find('[itemprop="ratingValue"]', 0)->innertext,
            $stock,
            null,
            null,
            null,
        ]);

        $dom->clear();
        return $item;
    }

    /**
     * レビューページからレビュー平均数を取得
     * @param $reviewCount
     * @param $standardParam
     * @param $index
     * @return int
     */
    private static function reviewAverage($reviewCount, $params1, $index)
    {
        $reviewAverage = 0;
        if ($reviewCount > 0) {
            $itemId = preg_replace('/(^.*?:)(\d*?)/', '$2', $params1['itemid'][$index]);
            $reviewPage = "https://review.rakuten.co.jp/item/1/{$params1['shopid'][0]}_{$itemId}/1.1/";
            $reviewContent = ScrapingUtil::get($reviewPage);
            if ($reviewContent && preg_match('/(class=.*?average.*?>)(.*?)(<)/', $reviewContent, $reviewMatch)) {
                $reviewAverage = $reviewMatch[2];
            }
        }
        return $reviewAverage;
    }

    /**
     * 埋め込みパラメータ1を取得
     * @param $content
     * @return array
     */
    private static function getParams1($content)
    {
        preg_match('/(var grp15_ias_prm = )({.*?})(;)/', $content, $matches);
        if (!$matches) {
            return false;
        }
        $param = str_replace("'", '"', $matches[2]);
        $param = preg_replace('/({|,)(\s*?)((?<!")\w*?)(:)/', '$1"$3"$4', $param);
        $param = json_decode($param, true);
        return $param;
    }

    /**
     * 埋め込みパラメータ2を取得
     * @param $dom
     * @return array
     */
    private static function getParams2($dom)
    {
        $param = $dom->find('#ratCustomParameters', 0)->value;
        $param = str_replace("'", '"', $param);
        $param = json_decode($param, true);
        return $param;
    }

    /**
     * ポイント倍率を取得
     * @param $cart
     * @return int
     */
    private static function pointRate($cart)
    {
        $prices = $cart->find('.price2');
        if (!empty($prices)) {
            foreach ($prices as $price) {
                $pattern = '/(\d*?)(倍 \d*?ポイント)/';
                if (preg_match($pattern, $price->plaintext, $match)) {
                    return (int)$match[1];
                }
            }
        }
        return 1;
    }

    /**
     * カートDOMを取得
     * @param $dom
     * @param $index
     * @return array
     */
    private static function cartDom($dom, $index)
    {
        $cart = $dom->find('#rakutenLimitedId_cart', $index);
        if ($cart) {
            return $cart;
        }
        return $dom->find('.rakutenLimitedId_cart', $index);
    }

    /**
     * カート位置を取得(通常カゴは0)
     * @param $content
     * @param $code
     * @return int
     */
    private static function cartIndex($content, $code)
    {
        // 複数カゴのDOM構造だとマッチ(ページ内リンクのアンカー(商品管理番号)を取得)
        preg_match_all('/<a name=\"([A-Za-z0-9_-]*?)\"><\/a><table[^<]*?><tr><td><span class=\"sale_desc\">/', $content, $matches);
        $index = array_search($code, $matches[1]);
        if ($index === false) {
            return 0;
        }
        return (int)$index;
    }

    /**
     * 楽天ブックページのURLを新フォーマットにする
     * @param $url
     * @return string
     */
    private static function newBoolUrl($url)
    {
        return str_replace('item.rakuten.co.jp/book', 'books.rakuten.co.jp/rb', $url);
    }

    /**
     * 楽天ブックページのURLか否か
     * @param $url
     * @return array
     */
    private static function isBookUrl($url)
    {
        // 楽天BOOKのURLを新フォーマットに
        $url = self::newBoolUrl($url);
        if (strpos($url, 'books.rakuten.co.jp/rb') !== false) {
            return true;
        }
        return false;
    }

    /**
     * 楽天の商品URLか否か
     * @param $url
     * @return boolean
     */
    private static function isItemUrl($url)
    {
        $pattern = '/^https:\/\/item\.rakuten\.co\.jp\/.*?\/.*?\/$/';
        if (preg_match($pattern, $url)) {
            return true;
        }
        return false;
    }
}
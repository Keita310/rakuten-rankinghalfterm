<?php

namespace App\Actions;

/**
 * Class StringUtil
 * 文字列関連のユーティリティ
 *
 * @package App\Actions
 */
class StringUtil
{
    /**
     * EUC文字列だったらUTF-8に変換
     *
     * @param $content UTF-8 or EUC文字列(楽天のページを想定)
     * @return string UTF-8文字列
     */
    public static function convertEncodingToUtf8($content)
    {
        if (stripos(mb_detect_encoding($content, 'JIS, eucjp-win, sjis-win'), 'EUC') !== false) {
            $content = mb_convert_encoding($content, 'UTF-8', 'EUC');
        }
        return $content;
    }

    /**
     * URLからshopIdを取得する
     *
     * @param $url URL文字列
     * @return string shopId文字列
     */
    public static function toShopId($url)
    {
        return preg_replace('/(^.*?.jp)(\/user\/|\/gold\/|\/)(.*?)(\/$|\/.*$|$)/', '$3', $url);
    }

    /**
     * 楽天の商品URLから管理番号を取得する
     * @param $url
     * @return string
     */
    public static function toItemCode($url)
    {
        return preg_replace('/(^.*?.jp\/.*?\/)(.*?)(\/)/', '$2', $url);
    }
}
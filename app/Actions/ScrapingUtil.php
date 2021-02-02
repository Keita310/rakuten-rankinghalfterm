<?php

namespace App\Actions;

use App\Actions\StringUtil;

/**
 * Class ScrapingUtil
 * スクレイピング関連のユーティリティ
 *
 * @package App\Actions
 */
class ScrapingUtil
{
    /**
     * 指定のURLからソースを取得
     *
     * @param $url string 文字列
     * @return string
     */
    public static function get($url, $device = 'pc')
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_USERAGENT => config('const.ua.' . $device),
        ]);
        $content = curl_exec($curl);
        // EUCだったらUTF-8に変換
        $content = StringUtil::convertEncodingToUtf8($content);
        curl_close($curl);
        return $content;
    }
}
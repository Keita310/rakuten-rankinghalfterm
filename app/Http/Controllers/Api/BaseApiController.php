<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Controller as BaseController;

/**
 * API関係のベースコントローラ
 */
class BaseApiController extends BaseController
{
    public function __construct()
    {
        //
    }

    /**
     * 成功レスポンス
     * @param array $data レスポンスデータ
     * @return mixed
     */
    protected function successResponse($data = [])
    {
        return response()->json([
            'status' => 'OK',
            'data' => $data,
        ], 200);
    }

    /**
     * エラーレスポンス
     * @param array $errors エラー内容
     * @return mixed
     */
    protected function errorResponse(array $errors)
    {
        return response()->json([
            'status' => 'NG',
            'errors' => $errors,
        ], 200);
    }
}

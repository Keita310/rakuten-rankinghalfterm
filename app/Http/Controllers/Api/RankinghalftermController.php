<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\Category;
use App\Models\Shop;
use App\Models\Items;

/**
 * 集計した半期ランキングデータを取得するコントローラ
 */
class RankinghalftermController extends BaseApiController
{
    public function __construct()
    {
        //
    }

    /**
     * ランキングデータを返す
     */
    public function index(Request $request)
    {
        // バリデーション
        $validated = $request->validate([
            'type' => 'required|in:shop,cate',
            'cate_season' => ['required', 'regex:/^[0-9]{4}_(first|last)$/'],
            'shop_code' => 'sometimes|string',
            'cate_code' => 'sometimes|string',
        ]);

        $type = (isset($validated['type'])) ? $validated['type'] : null;
        $cateCode = (isset($validated['cate_code'])) ? $validated['cate_code'] : null;
        $shopCode = (isset($validated['shop_code'])) ? $validated['shop_code'] : null;

        switch ($type) {
            case 'shop':
                $data = Shop::get($validated['cate_season'], $shopCode);
                break;

            case 'cate':
                $data = Category::get($validated['cate_season'], $cateCode);
                break;
        }

        return $this->successResponse($data);
    }
}

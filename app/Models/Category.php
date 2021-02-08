<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = array('id');
    protected $fillable = [];

    /**
     * itemsテーブルとのリレーション
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function items()
    {
        return $this->hasMany(Items::class, 'cate_id');
    }

    /**
     * カテゴリ毎で抽出する
     * @return array
     */
    public static function get($cateSeason, $cateCode = null)
    {
        $where = ['cate_season' => $cateSeason];
        if ($cateCode) {
            $where['cate_code'] = $cateCode;
        }
        return self::where($where)
            ->with('items', 'items.shop')
            ->get();
    }
}
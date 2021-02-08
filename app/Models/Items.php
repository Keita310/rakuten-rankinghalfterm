<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    protected $guarded = array('id');
    protected $fillable = [];

    /**
     * categoriesテーブルとのリレーション
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function category()
    {
        return $this->hasOne(Category::class, 'cate_id', 'cate_id');
    }

    /**
     * shopsテーブルとのリレーション
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function shop()
    {
        return $this->hasOne(Shop::class, 'shop_id', 'shop_id');
    }
}
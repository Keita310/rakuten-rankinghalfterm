<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('cate_id');
            $table->bigInteger('shop_id');
            $table->tinyInteger('rank');
            $table->string('code', 255);
            $table->text('name');
            $table->text('catchcopy')->nullable();
            $table->integer('price');
            $table->string('image', 255);
            $table->string('url', 255);
            $table->tinyInteger('point_rate');
            $table->integer('review_count')->nullable();
            $table->string('review_average', 255)->nullable();
            $table->boolean('soldout');
            $table->string('asuraku_flag', 255)->nullable();
            $table->string('postage_flag', 255)->nullable();
            $table->string('availability', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('items');
    }
}

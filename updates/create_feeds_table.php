<?php namespace VojtaSvoboda\ShopaholicFeeds\Updates;

use DB;
use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateFeedsTable extends Migration
{
    public function up()
    {
        Schema::create('vojtasvoboda_shopaholicfeeds_feeds', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('locale_id')->unsigned()->nullable();
            if (Schema::hasTable('rainlab_translate_locales') === true) {
                $table->foreign('locale_id')->references('id')->on('rainlab_translate_locales');
            }
            $table->integer('currency_id')->unsigned()->nullable();
            $table->foreign('currency_id')->references('id')->on('lovata_shopaholic_currency');
            $table->string('name', 300);
            $table->string('slug', 191)->unique();
            $table->char('hash', 16)->unique();
            $table->string('format', 300);
            $table->string('product_page', 300);
            $table->text('ip_addresses')->nullable();
            $table->boolean('only_private')->default(true);
            $table->boolean('log_enabled')->default(true);
            $table->boolean('enabled')->default(true);
            $table->integer('created_by')->unsigned();
            $table->foreign('created_by', 'create_backend_user')->references('id')->on('backend_users');
            $table->timestamp('created_at')->useCurrent();
            $table->integer('updated_by')->unsigned();
            $table->foreign('updated_by', 'update_backend_user')->references('id')->on('backend_users');
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::dropIfExists('vojtasvoboda_shopaholicfeeds_feeds');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}

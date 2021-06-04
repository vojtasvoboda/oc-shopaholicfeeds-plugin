<?php namespace VojtaSvoboda\ShopaholicFeeds\Updates;

use DB;
use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateLogsTable extends Migration
{
    public function up()
    {
        Schema::create('vojtasvoboda_shopaholicfeeds_feed_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('feed_id')->unsigned();
            $table->foreign('feed_id')
                ->references('id')
                ->on('vojtasvoboda_shopaholicfeeds_feeds')
                ->onDelete('CASCADE');
            $table->string('remote_addr', 20);
            $table->string('uri', 600);
            $table->string('method', 20);
            $table->string('params', 600)->nullable();
            $table->decimal('took_seconds')->nullable();
            $table->boolean('allowed')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::dropIfExists('vojtasvoboda_shopaholicfeeds_feed_logs');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}

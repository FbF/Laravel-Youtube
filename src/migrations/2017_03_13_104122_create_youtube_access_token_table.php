<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateYoutubeAccessTokenTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(\Config::get('youtube.table_name'), function(Blueprint $table)
        {
            $table->increments('id');
            if(\Config::get('youtube.auth') == true){
                $table->integer('user_id');
            }
            $table->text('access_token');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(\Config::get('youtube.table_name'));
    }

}

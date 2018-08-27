<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->increments('id');
	        $table->string('users_user_id');
	        $table->string('client_id')->nullable();
	        $table->date('date');
	        $table->string('cost')->nullable();
	        $table->string('product');
	        $table->time('time');
	        $table->time('duration');
	        $table->integer('discount')->nullable();
	        $table->string('text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('services');
    }
}

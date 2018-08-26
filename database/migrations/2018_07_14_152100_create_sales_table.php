<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('sales', function (Blueprint $table) {
			$table->increments('id');
			$table->string('users_user_id');
			$table->string('client_id')->nullable();
			$table->date('date');
			$table->string('cost')->nullable();
			$table->string('product');
			$table->integer('discount')->nullable();
			$table->integer('count');
		//	$table->string('text')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('sales');
	}
}

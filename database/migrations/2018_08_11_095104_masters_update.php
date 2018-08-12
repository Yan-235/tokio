<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MastersUpdate extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('masters', function (Blueprint $table) {
			$table->increments('id');
			$table->string('name');
			$table->string('salon');
			$table->integer('range');
			$table->integer('plan');
			$table->float('zp');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('masters');
	}
}

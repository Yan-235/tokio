<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ClientsUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

	public function up() {
		Schema::create('clients', function (Blueprint $table) {
			$table->increments('id');
			$table->string('name');
			$table->string('tel')->nullable();
			$table->string('address')->nullable();
		//	$table->fulltext('name');
		//	$table->fulltext('tel');
		});
		DB::statement('ALTER TABLE clients ADD FULLTEXT fulltext_index (name,tel)');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('clients');
	}
}

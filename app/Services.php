<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Services extends Model {
	public $timestamps = false;

	protected $fillable = [
		'users_user_id', 'client_id', 'product', 'date', 'cost', 'time', 'duration','discount','text'
	];
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sales extends Model {

	public $timestamps = false;

	protected $fillable = [
		'users_user_id', 'client_id', 'product', 'date', 'cost', 'discount', 'text', 'count'
	];
}

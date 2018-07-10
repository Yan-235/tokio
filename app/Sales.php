<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sales extends Model {

	protected $fillable = [
		'users_user_id', 'number_of_units', 'date', 'cost', 'time', 'duration'
	];
}

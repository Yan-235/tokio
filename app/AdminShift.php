<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminShift extends Model
{
	public $timestamps = false;

    protected $fillable = [
        'id', 'date', 'admin_id', 'shift_type'
    ];
}

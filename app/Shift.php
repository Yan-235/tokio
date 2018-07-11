<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
	public $timestamps = false;

    protected $fillable = [
        'master_id', 'date','shift_type','start_shift','end_shift'
    ];

}

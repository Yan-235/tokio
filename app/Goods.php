<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Goods extends Model
{
	protected $fillable = [
     'id', 'good_name', 'good_cost', 'salon'
 ];
}

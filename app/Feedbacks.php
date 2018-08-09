<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Feedbacks extends Model
{

	public $timestamps = false;

	protected $fillable = [
     'id', 'master_id', 'product_id', 'feedback'
 ];
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Administator extends Model
{
    protected $fillable = [
        'id', 'name', 'salon', 'day_payment'
    ];
}

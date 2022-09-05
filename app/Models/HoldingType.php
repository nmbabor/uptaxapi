<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoldingType extends Model
{
    protected $table = 'holding_type';
    protected $fillable = ['name','sl_no'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bazar extends Model
{
    protected $table = 'bazars';
    protected $fillable = ['union_id','name','bn_name','word'];
}

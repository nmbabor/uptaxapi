<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Union extends Model
{
    protected $table = 'unions';
    protected $fillable = ['upazila_id','name','bn_name','word'];
}

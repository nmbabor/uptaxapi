<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserUnion extends Model
{
     protected $table = 'user_unions';
    protected $fillable = ['union_id','user_id'];
}

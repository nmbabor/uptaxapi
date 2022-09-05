<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxList extends Model
{
    protected $table = 'tax_lists';
    protected $fillable = ['year_id','tax','type'];
}

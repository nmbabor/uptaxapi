<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnionBillDetails extends Model
{
    protected $table = 'union_bill_details';
    protected $fillable = ['union_id','chairman_name','chairman_mobile','bank_name','branch_name','bill_start_date','bill_end_date','signature','logo','email','details','created_by'];
}

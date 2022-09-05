<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxListYearly extends Model
{
    protected $table = 'tax_list_yearly';
    protected $fillable = ['invoice','year','tax','prev_due','static_due','discount','total_amount','total_paid','prev_paid','last_payment_date','holding_id','created_by','payment_status','sms_send'];

    public function holding(){
        return $this->belongsTo(Holdings::class,'holding_id','id');
    }
    public function yearData(){
        return $this->belongsTo(Years::class,'year','id');
    }


}


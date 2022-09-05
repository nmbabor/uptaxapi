<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeLicenceFeeYearly extends Model
{
    protected $table = 'trade_licence_fee_yearly';
    protected $fillable = ['invoice','year','tax','prev_due','static_due','discount','total_amount','total_paid','prev_paid','last_payment_date','trade_licence_id','created_by','payment_status','sms_send'];

    public function tradeLicence(){
        return $this->belongsTo(TradeLicence::class,'trade_licence_id','id');
    }
    public function yearData(){
        return $this->belongsTo(Years::class,'year','id');
    }
}

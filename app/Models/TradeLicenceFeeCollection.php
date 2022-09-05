<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeLicenceFeeCollection extends Model
{
    protected $table = 'trade_licence_fee_collections';
    protected $fillable = ['trade_licence_list_id','collect_by','payment_date','paid_amount','due_amount','invoice_id','receipt_no'];

    public function taxList(){
        return $this->belongsTo(TradeLicenceFeeYearly::class,'trade_licence_list_id','id');
    }
}

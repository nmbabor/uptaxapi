<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxCollection extends Model
{
    protected $table = 'tax_collections';
    protected $fillable = ['tax_list_id','collect_by','payment_date','paid_amount','due_amount','invoice_id','receipt_no'];

    public function taxList(){
        return $this->belongsTo(TaxListYearly::class,'tax_list_id','id');
    }



}


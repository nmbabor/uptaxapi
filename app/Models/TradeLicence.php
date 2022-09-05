<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeLicence extends Model
{
    protected $table = 'trade_licence';
    protected $fillable = ['trade_licence_no','holding_no','owner_name','organization_name','business_type','father_or_husband','mother','mobile','religion','gender','nid','annual_tax','tax_due','tax_due_add','others_bill_details','others_bill','union_id','word','bazar_id','current_year','created_by','updated_by'];

    public function union(){
        return $this->belongsTo(Union::class,'union_id','id');
    }
    public function bazar(){
        return $this->belongsTo(Bazar::class,'bazar_id','id');
    }
    public function taxData(){
        return $this->hasOne(TradeLicenceFeeYearly::class,'trade_licence_id','id')->latest();
    }
}

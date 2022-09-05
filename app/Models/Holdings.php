<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holdings extends Model
{
    protected $table = 'holdings';
    protected $fillable = ['type','holding_no','owner_name','organization_name','business_type','father_or_husband','mother','mobile','profession','education','religion','gender','birthday','nid','got_social_benefits','get_social_benefits','eligible_for_social_benefits','tube_well','toilet',
    'house_unripe',
    'house_bhite_paka',
    'house_semi_ripe',
    'house_ripe',
    'annual_assessment','annual_tax','tax_due','tax_due_add','others_bill_details','others_bill','union_id','word','village_id','current_year','created_by','updated_by'];

    public function union(){
        return $this->belongsTo(Union::class,'union_id','id');
    }
    public function village(){
        return $this->belongsTo(Village::class,'village_id','id');
    }
    public function taxData(){
        return $this->hasOne(TaxListYearly::class,'holding_id','id')->latest();
    }
}

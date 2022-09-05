<?php

namespace App\Http\Controllers;

use App\Models\TaxListYearly;
use App\Models\Union;
use App\Models\UnionBillDetails;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function singleHoldingSms($id){
        $tarYear = TaxListYearly::findOrFail($id);
        $holding = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')
            ->leftJoin('years','tax_list_yearly.year','years.id')
            ->leftJoin('villages','holdings.village_id','villages.id')
            ->select('tax_list_yearly.id','holding_id','holding_no','tax_list_yearly.invoice','organization_name','father_or_husband','owner_name','holdings.type','holdings.word',
                'tax_list_yearly.tax','tax_list_yearly.prev_due','tax_list_yearly.total_amount','tax_list_yearly.discount','tax_list_yearly.total_paid','tax_list_yearly.payment_status','years.name as year_name',
                'holdings.mobile','village_id','villages.bn_name as village_name','holdings.union_id','last_payment_date','mobile')
            ->where('tax_list_yearly.id',$id)
            ->first();
        $area = Union::leftJoin('upazilas','upazila_id','upazilas.id')
            ->leftJoin('districts','district_id','districts.id')
            ->where('unions.id',$holding->union_id)
            ->select('districts.bn_name as district_name','upazilas.bn_name as upazila_name','unions.bn_name as union_name')->first();
        $details = UnionBillDetails::where('union_id',$holding->union_id)->first();

        /*$msg = $holding->owner_name.'। পিতা/স্বামীঃ '.$holding->father_or_husband.'হোল্ডিং নং-'.\MyHelper::en2bn($holding->holding_no).'
ক্রমিক নং '.\MyHelper::en2bn($holding->invoice).' ঠিকানাঃ- ওয়ার্ডঃ '.\MyHelper::en2bn($holding->word).', গ্রামঃ '.$holding->village_name.'
অর্থবছরঃ '.\MyHelper::en2bn($holding->year_name).' । ট্যাক্সঃ বকেয়া '.\MyHelper::en2bn($holding->prev_due).'৳, হালঃ '.\MyHelper::en2bn($holding->tax).'৳,';
        if($holding->total_paid>0){
            $msg.='জমাঃ '.\MyHelper::en2bn($holding->total_paid).'৳,';
        }
        if($holding->discount>0){
            $msg.='ডিসকাউন্টঃ '.\MyHelper::en2bn($holding->discount).'৳,';
        }
        $msg.='সর্বমোটঃ '.\MyHelper::en2bn($holding->total_amount-$holding->total_paid).'৳ ।
জমাদানের শেষ তারিখঃ '.\MyHelper::en2bn(date('d-m-Y',strtotime($details->bill_end_date))).' ।
নিয়মিত ইউ.পি ট্যাক্স পরিশোধ করুন, ইউনিয়নের উন্নয়নে অংশ নিন ।
'.$area->union_name.' ইউনিয়ন পরিষদ ।';*/
        $shortYear = explode('-',$holding->year_name);
        $sYear = substr($shortYear[0],-2).'-'.substr($shortYear[1],-2);
        $msg = $holding->owner_name.','.\MyHelper::en2bn($sYear).' ৳'.\MyHelper::en2bn($holding->total_amount-$holding->total_paid).' কর দিন সেবা নিন, '.$area->union_name.' ইউপি';
        if(strlen($holding->mobile)<11){
            return response('Mobile number not valid!',400);
        }
        $mobile = $holding->mobile;
        //$mobile = "01811951215";
        $result = \SMS::single($mobile,$msg);
        if($result=='True'){
            $tarYear->update([
                'sms_send'=>$tarYear->sms_send+1
            ]);
            return response('Successfully send sms.',200);
        }else{
            return response('Error: '.$result,400);
        }

    }
}

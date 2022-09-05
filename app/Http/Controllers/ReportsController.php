<?php

namespace App\Http\Controllers;

use App\Models\HoldingType;
use App\Models\UnionBillDetails;
use Illuminate\Http\Request;
use App\Models\Holdings;
use App\Models\Village;
use App\Models\Union;
use App\Models\TaxCollection;
use DB;
use App\Models\Years;
use App\Models\TaxListYearly;

class ReportsController extends Controller
{
    public function holdings(Request $request){
         $credentials = [
              'type'=>'সকল'
         ];
        $type = $request->type;
        $allData = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')
        ->leftJoin('villages','holdings.village_id','villages.id')
        ->leftJoin('years','tax_list_yearly.year','years.id')
        ->select('tax_list_yearly.id','holding_id','holding_no','invoice','organization_name','business_type','house_ripe','house_semi_ripe','house_bhite_paka','house_unripe','annual_assessment','others_bill','father_or_husband','owner_name','holdings.type','holdings.word','villages.bn_name as village_name','tax_list_yearly.tax','tax_list_yearly.prev_due','tax_list_yearly.total_amount','tax_list_yearly.discount','tax_list_yearly.total_paid','tax_list_yearly.prev_paid','tax_list_yearly.payment_status','years.name as year_name','holdings.mobile','tax_list_yearly.sms_send')
        ->where('tax_list_yearly.year',$request->year)
        ->orderBy('invoice','ASC');
        if($request->keyword!=''){
            $keyword = $request->keyword;
            $allData = $allData->where(function ($query) use($keyword) {
                  $columns = ['holding_no','owner_name','organization_name','father_or_husband','mother','mobile'];
                  foreach($columns as $column){
                        $query->orWhere($column, 'LIKE', '%' . $keyword . '%');
                }
                return $query;
            });
        }

        if($type!=0){
             $credentials['type'] = HoldingType::where('id',$type)->value('name');
            $allData = $allData->where('type',$type);
        }
        if($request->year!=''){
            $credentials['year'] = Years::where('id',$request->year)->value('name');
        }
        if($request->village_id!=''){
             $credentials['village'] = Village::where('id',$request->village_id)->value('bn_name');
             $allData = $allData->where('holdings.village_id',$request->village_id);
        }
        if(isset($request->payment_status) && $request->payment_status!=''){
             $allData = $allData->where('tax_list_yearly.payment_status',$request->payment_status);
        }
        if(isset($request->paid_status) && $request->paid_status!=''){
             $allData = $allData->where('tax_list_yearly.total_paid','>',0);
        }
        if($request->union_id!=''){
             $credentials['union'] = Union::where('id',$request->union_id)->value('bn_name');
             $allData = $allData->where('holdings.union_id',$request->union_id);
            $details = UnionBillDetails::where('union_id',$request->union_id)->first();
            $credentials['union_details'] = $details->details;
        }
        if($request->word!=''){
             $words = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
             $credentials['word'] = $words[$request->word];
             $allData = $allData->where('holdings.union_id',$request->union_id)->where('holdings.word',$request->word);
        }
        $allData = $allData->paginate($request->perpage);
        return response()->json(['allData'=>$allData,'credentials'=>$credentials], 200);
    }
    public function dailyTax(Request $request){
        $from = date('Y-m-d',strtotime($request->start_date));
        $to = date('Y-m-d',strtotime($request->end_date));
        $allData = TaxCollection::leftJoin('holdings','holding_id','holdings.id')
        ->leftJoin('villages','holdings.village_id','villages.id')
        ->leftJoin('years','tax_collections.year','years.id')
        ->select('tax_collections.id','holding_id','holding_no','owner_name','holdings.type','holdings.word','villages.bn_name as village_name','tax_collections.tax','tax_collections.payment_date','years.name as year_name','holdings.mobile')
        ->whereBetween('payment_date',[$from,$to])->orderBy('holding_no','ASC');
        if($request->keyword!=''){
            $keyword = $request->keyword;
            $allData = $allData->where(function ($query) use($keyword) {
                  $columns = ['holding_no','owner_name','organization_name','father_or_husband','mother','mobile'];
                  foreach($columns as $column){
                        $query->orWhere($column, 'LIKE', '%' . $keyword . '%');
                }
                return $query;
            });
        }
        if($request->village_id!=''){
             $allData = $allData->where('holdings.village_id',$request->village_id);
        }
        if($request->union_id!=''){
             $allData = $allData->where('holdings.union_id',$request->union_id);
        }
        if($request->word!=''){
             $allData = $allData->where('holdings.union_id',$request->union_id)->where('holdings.word',$request->word);
        }
        $total = $allData->sum('tax');
        $allData = $allData->get();
        $main = ['allData'=>$allData,'total'=>$total];
        return response()->json($main, 200);
    }
    // Due Report not used any where
    public function dueReport(Request $request){

        $credentials = [
             'type'=>'সকল',
             'year'=>Years::where('id',$request->year)->value('name')
        ];
       $type = $request->type;
       $allData = TaxCollection::leftJoin('holdings','holding_id','holdings.id')
       ->groupBy('holding_id')
       ->select('holding_id','holding_no','owner_name','organization_name','father_or_husband','mobile','annual_tax')
       ->where('payment_status',0)->orderBy('word','ASC');

       if($type!=0){
            $credentials['type'] = HoldingType::where('id',$type)->value('name');
           $allData = $allData->where('type',$type);
       }
       if($request->village_id!=''){
            $credentials['village'] = Village::where('id',$request->village_id)->value('bn_name');
            $allData = $allData->where('village_id',$request->village_id);
       }
       if($request->union_id!=''){
            $credentials['union'] = Union::where('id',$request->union_id)->value('bn_name');
            $allData = $allData->where('union_id',$request->union_id);
           $details = UnionBillDetails::where('union_id',$request->union_id)->first();
           $credentials['union_details'] = $details->details;
       }
       if($request->word!=''){
            $words = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            $credentials['word'] = $words[$request->word];
            $allData = $allData->where('union_id',$request->union_id)->where('word',$request->word);

       }
       $allHoldings = $allData->pluck('holding_id');
       $currentYear = TaxCollection::whereIn('holding_id',$allHoldings)->where(['year'=>$request->year,'payment_status'=>0])->pluck('tax','holding_id');

       $prevYear = TaxCollection::where('year','!=',$request->year)->whereIn('holding_id',$allHoldings)->where('payment_status',0)
       ->leftJoin('years','year','years.id')
       ->groupBy('holding_id')
       ->select('holding_id',DB::raw('SUM(tax_collections.tax) as due_tax'),DB::raw("(GROUP_CONCAT(years.name SEPARATOR ',')) as `due_years`"))->get()->keyBy('holding_id');
       $allData = $allData->paginate($request->perpage);
       return response()->json(['allData'=>$allData,'currentYear'=>$currentYear,'prevYear'=>$prevYear,'credentials'=>$credentials], 200);
   }

   public function singleHoldingBill(Request $request){

       $holding = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')
           ->leftJoin('holding_type','holdings.type','holding_type.id')
        ->leftJoin('years','tax_list_yearly.year','years.id')
        ->leftJoin('villages','holdings.village_id','villages.id')
        ->select('tax_list_yearly.id','holding_id','holding_no','tax_list_yearly.invoice','organization_name','business_type','father_or_husband','owner_name','holdings.type','holdings.word',
        'tax_list_yearly.tax','tax_list_yearly.prev_due','tax_list_yearly.total_amount','tax_list_yearly.discount','tax_list_yearly.total_paid','tax_list_yearly.payment_status','years.name as year_name',
        'holdings.mobile','village_id','villages.bn_name as village_name','holdings.union_id','last_payment_date','mobile','tax_list_yearly.sms_send','holding_type.name as type_name')
        ->where(['tax_list_yearly.year'=>$request->year])
        ->whereIn('holding_id',$request->holding_id)
        ->orderBy('word','ASC')->orderBy('holding_no','ASC')
        ->get();
       $i=1;
       foreach($holding as $hold){
           $hold->index = $i++;
       }
        if(count($holding)==0){
             $allData = [
            'holding'=>'',
            'area'=>'',
       ];
       return response()->json($allData, 200);
        }
       $area = Union::leftJoin('upazilas','upazila_id','upazilas.id')
       ->leftJoin('districts','district_id','districts.id')
       ->where('unions.id',$request->union_id)
       ->select('districts.bn_name as district_name','upazilas.bn_name as upazila_name','unions.bn_name as union_name')->first();

       $allData = [
            'holding'=>$holding,
            'area'=>$area,
       ];
       return response()->json($allData, 200);


   }
   public function autoSuggestions(Request $request){

        try{

        $allData = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')
        ->select('holding_id as value',DB::raw("CONCAT(holding_no,'(',owner_name,')') as label"))
        ->where('union_id',$request->union_id)
        ->orderBy('word','ASC')->orderBy('holding_no','ASC')
        ->where(['year'=>$request->year]);
        if($request->value!=''){
            $allData = $allData->where(function ($q) use($request) {
                $q->where('holding_no','LIKE','%'.$request->value.'%')->orWhere('owner_name','LIKE','%'.$request->value.'%');
            });
        }
        $allData = $allData->limit(10)->get();
        return response()->json($allData, 200);
        }catch(\Exception $e){
            return response($e->errorInfo[2],500);
        }
   }

   // Due Report
    public function search(Request $request){
         $year = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')->where('holdings.union_id',$request->union_id)
        ->orderBy('tax_list_yearly.year','DESC')->select('tax_list_yearly.year')->value('year');

        $allData = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')
        ->leftJoin('villages','holdings.village_id','villages.id')
        ->leftJoin('years','tax_list_yearly.year','years.id')
        ->select('tax_list_yearly.id','tax_list_yearly.year','holding_id','holding_no','organization_name','business_type','house_ripe','house_semi_ripe','house_bhite_paka','house_unripe','annual_assessment','others_bill','father_or_husband','owner_name','holdings.type','holdings.word','villages.bn_name as village_name','tax_list_yearly.tax','tax_list_yearly.prev_due','tax_list_yearly.total_amount','tax_list_yearly.discount','tax_list_yearly.total_paid','tax_list_yearly.payment_status','years.name as year_name','holdings.mobile')
        ->where('tax_list_yearly.year',$year)
        ->where('holdings.union_id',$request->union_id)
        ->groupBy('holding_id')
        ->orderBy('word','ASC')->orderBy('holding_no','ASC');
        if($request->keyword!=''){
            $keyword = $request->keyword;
            $allData = $allData->where(function ($query) use($keyword) {
                $query->orWhere('holding_no', 'LIKE', $keyword . '%');
                  $columns = ['owner_name','organization_name','father_or_husband','mother'];
                  foreach($columns as $column){
                        $query->orWhere($column, 'LIKE', '%' . $keyword . '%');
                }
                if(strlen($keyword)>9){
                $query->orWhere('mobile', 'LIKE', '%' . $keyword . '%');
                }
                return $query;
            });
        }

        $allData = $allData->limit(100)->get();
        return response()->json($allData, 200);
   }
   //Previous Tax collection
    public function prevTaxCollection(Request $request){
        $credentials = [
            'type'=>'সকল'
        ];
        $type = $request->type;
        $allData = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')
            ->leftJoin('villages','holdings.village_id','villages.id')
            ->leftJoin('years','tax_list_yearly.year','years.id')
            ->select('tax_list_yearly.id','holding_id','holding_no','invoice','organization_name','business_type','house_ripe','house_semi_ripe','house_bhite_paka','house_unripe','annual_assessment','others_bill','father_or_husband','owner_name','holdings.type','holdings.word','villages.bn_name as village_name','tax_list_yearly.tax','tax_list_yearly.prev_due','tax_list_yearly.total_amount','tax_list_yearly.discount','tax_list_yearly.total_paid','tax_list_yearly.prev_paid','tax_list_yearly.last_payment_date','tax_list_yearly.payment_status','years.name as year_name','holdings.mobile')
            ->where('tax_list_yearly.year',$request->year)
            ->where('tax_list_yearly.prev_paid','>',0)
            ->orderBy('invoice','ASC');

        if($type!=0){
            $credentials['type'] = HoldingType::where('id',$type)->value('name');
            $allData = $allData->where('type',$type);
        }
        if($request->year!=''){
            $credentials['year'] = Years::where('id',$request->year)->value('name');
        }
        if($request->village_id!=''){
            $credentials['village'] = Village::where('id',$request->village_id)->value('bn_name');
            $allData = $allData->where('holdings.village_id',$request->village_id);
        }

        if($request->union_id!=''){
            $credentials['union'] = Union::where('id',$request->union_id)->value('bn_name');
            $allData = $allData->where('holdings.union_id',$request->union_id);
            $details = UnionBillDetails::where('union_id',$request->union_id)->first();
            $credentials['union_details'] = $details->details;
        }
        if($request->word!=''){
            $words = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            $credentials['word'] = $words[$request->word];
            $allData = $allData->where('holdings.union_id',$request->union_id)->where('holdings.word',$request->word);
        }
        $allData = $allData->paginate($request->perpage);
        return response()->json(['allData'=>$allData,'credentials'=>$credentials], 200);
    }

    //Current Year Tax collection
    public function currentTaxCollection(Request $request){
        $credentials = [
            'type'=>'সকল'
        ];
        $type = $request->type;
        $allData = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')
            ->leftJoin('villages','holdings.village_id','villages.id')
            ->leftJoin('years','tax_list_yearly.year','years.id')
            ->select('tax_list_yearly.id','holding_id','holding_no','invoice','organization_name','business_type','house_ripe','house_semi_ripe','house_bhite_paka','house_unripe','annual_assessment','others_bill','father_or_husband','owner_name','holdings.type','holdings.word','villages.bn_name as village_name','tax_list_yearly.tax','tax_list_yearly.prev_due','tax_list_yearly.total_amount','tax_list_yearly.discount','tax_list_yearly.total_paid','tax_list_yearly.prev_paid','tax_list_yearly.last_payment_date','tax_list_yearly.payment_status','years.name as year_name','holdings.mobile')
            ->where('tax_list_yearly.year',$request->year)
            ->whereColumn('tax_list_yearly.total_paid','>','tax_list_yearly.prev_due')
            ->orderBy('invoice','ASC');

        if($type!=0){
            $credentials['type'] = HoldingType::where('id',$type)->value('name');
            $allData = $allData->where('type',$type);
        }
        if($request->year!=''){
            $credentials['year'] = Years::where('id',$request->year)->value('name');
        }
        if($request->village_id!=''){
            $credentials['village'] = Village::where('id',$request->village_id)->value('bn_name');
            $allData = $allData->where('holdings.village_id',$request->village_id);
        }

        if($request->union_id!=''){
            $credentials['union'] = Union::where('id',$request->union_id)->value('bn_name');
            $allData = $allData->where('holdings.union_id',$request->union_id);
            $details = UnionBillDetails::where('union_id',$request->union_id)->first();
            $credentials['union_details'] = $details->details;
        }
        if($request->word!=''){
            $words = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            $credentials['word'] = $words[$request->word];
            $allData = $allData->where('holdings.union_id',$request->union_id)->where('holdings.word',$request->word);
        }
        $allData = $allData->paginate($request->perpage);
        return response()->json(['allData'=>$allData,'credentials'=>$credentials], 200);
    }





}

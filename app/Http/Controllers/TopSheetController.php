<?php

namespace App\Http\Controllers;

use App\Models\HoldingType;
use App\Models\TaxListYearly;
use App\Models\Years;
use Illuminate\Http\Request;
use DB;
class TopSheetController extends Controller
{
    public function index(Request $request){
        $union_id = $request->union_id;
        $year = $request->year;
        $type = $request->type;
        $credentials = [
            'type'=>'সকল',
            'year'=>Years::where('id',$request->year)->value('name')
        ];
        $query = TaxListYearly::LeftJoin('holdings','holding_id','holdings.id')
            ->leftJoin('years','tax_list_yearly.year','years.id')
            ->select('holdings.word',DB::raw('COUNT(DISTINCT holdings.village_id) as village'),DB::raw('COUNT(DISTINCT holding_id) as holding'),DB::raw('SUM(tax_list_yearly.tax) as current_tax'),DB::raw('SUM(tax_list_yearly.total_paid) as total_paid'),DB::raw('SUM(tax_list_yearly.total_amount) as total_amount'),DB::raw('SUM(tax_list_yearly.prev_due) as total_prev_due'),DB::raw('SUM(tax_list_yearly.prev_paid) as total_prev_paid'),'years.name as year_name')

            ->where('tax_list_yearly.year',$year)
            ->where('holdings.union_id',$union_id)
            ->groupBy('holdings.word')
            ->orderBy('holdings.word','ASC');
        if($type!=0){
            $credentials['type'] =  HoldingType::where('id',$type)->value('name');
            $query = $query->where('holdings.type',$request->type);
        }
        $allData = $query->get();
        foreach($allData as $key => $data){
            $paid = TaxListYearly::LeftJoin('holdings','holding_id','holdings.id')
                ->where(['tax_list_yearly.year'=>$year])
                ->where(['holdings.union_id'=>$union_id,'holdings.word'=>$data->word])
                ->where('total_paid','>',0);
                 if($type!=0){
                     $paid = $paid->where('holdings.type',$request->type);
                 }
                $paid = $paid->count();
            $data->paid_holdings=$paid;
        }

        return response()->json(['allData'=>$allData,'credentials'=>$credentials], 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\HoldingType;
use App\Models\TaxCollection;
use App\Models\Holdings;
use Illuminate\Http\Request;
use App\Models\Years;
use Validator,DB;
use App\Models\Union;
use App\Models\TaxListYearly;
use App\Models\Village;

class TaxCollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $credentials = [
            'type'=>'সকল'
       ];
      $type = $request->type;
      $allData = TaxCollection::leftJoin('tax_list_yearly','tax_list_id','tax_list_yearly.id')
      ->leftJoin('holdings','holding_id','holdings.id')
      ->leftJoin('villages','holdings.village_id','villages.id')
      ->leftJoin('years','tax_list_yearly.year','years.id')
      ->select('tax_collections.id','tax_collections.paid_amount','tax_collections.invoice_id','tax_collections.due_amount','tax_collections.payment_date','tax_collections.receipt_no','holding_id','holding_no','organization_name','father_or_husband','owner_name','holdings.type','holdings.word','villages.bn_name as village_name','tax_list_yearly.tax','tax_list_yearly.prev_due','tax_list_yearly.total_amount','tax_list_yearly.total_paid','tax_list_yearly.payment_status','tax_list_yearly.discount','years.name as year_name','holdings.mobile')
      ->where('tax_list_yearly.year',$request->year)
      ->orderBy('tax_collections.id','DESC');
      if($type!=0){
           $credentials['type'] = HoldingType::where('id',$type)->value('name');
          $allData = $allData->where('type',$type);
      }
      if($request->year!=''){
          $credentials['year'] = Years::where('id',$request->year)->value('name');
      }

      if($request->keyword!=''){
         $keyword = $request->keyword;

          $allData = $allData->where(function ($query) use($keyword) {
              $columns = ['receipt_no','invoice_id','payment_date','holdings.holding_no','owner_name','organization_name','father_or_husband','mother','mobile'];
              foreach($columns as $column){
                    $query->orWhere($column, 'LIKE', '%' . $keyword . '%');
            }
            return $query;
        });
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
      }
      if($request->word!=''){
           $words = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
           $credentials['word'] = $words[$request->word];
           $allData = $allData->where('holdings.union_id',$request->union_id)->where('holdings.word',$request->word);
      }
      if(isset($request->start_date) && isset($request->end_date)){
        $from = date('Y-m-d',strtotime($request->start_date));
        $to = date('Y-m-d',strtotime($request->end_date));
        if($from=='1970-01-01'){
            $from = date('Y-m-d');
        }
        if($to=='1970-01-01'){
            $to = date('Y-m-d');
        }
        $credentials['date'] = $from.' থেকে '.$to;
        if($from==$to){
            $credentials['date'] = $to;
        }
        $allData = $allData->whereBetween('payment_date',[$from,$to]);

      }

      $allData = $allData->paginate($request->perpage);
      return response()->json(['allData'=>$allData,'credentials'=>$credentials], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        try{
        $type = $request->type;

        $allData = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')
        ->select('tax_list_yearly.id as value',DB::raw("CONCAT(holding_no,'(',owner_name,')') as label"))
        ->orderBy('holding_no','ASC')
        ->where(['year'=>$request->year,'payment_status'=>0]);

        if($request->village_id!=''){
             $allData = $allData->where('village_id',$request->village_id);
        }
        if($request->union_id!=''){
             $allData = $allData->where('union_id',$request->union_id);
        }
        if($request->word!=''){
             $allData = $allData->where('union_id',$request->union_id)->where('word',$request->word);
        }
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paid_amount'  => 'required',
            'tax_list_id'  => 'required',
            'payment_date' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 403);
        }

         try{

            $input = $request->all();
            $input['collect_by']=\Auth::user()->id;
            $payment_date=date('Y-m-d',strtotime($request->payment_date));
            if($payment_date>date('Y-m-d') || $payment_date=='1970-01-01'){
                $payment_date = date('Y-m-d');
            }
            $input['payment_date']=$payment_date;
            $taxYear = TaxListYearly::findOrFail($request->tax_list_id);
            if($taxYear->payment_status==1){
                return response()->json('Already Paid.',200);
            }
             if(isset($request->discount)){
                 $mainAmount = $taxYear->prev_due+$taxYear->tax;
                 $totalAmount = $mainAmount-$request->discount;
                 $taxYearUpdate = [];
                 $taxYearUpdate['total_amount'] = $totalAmount;
                 $taxYearUpdate['discount'] = $request->discount;
                 $taxYear->update($taxYearUpdate);
             }
            $maxId = TaxCollection::where('tax_list_id',$request->tax_list_id)->count();
            if($maxId>0){
                $maxId +=1;
                $input['invoice_id'] = $taxYear->invoice.'-'.$maxId;
            }else{
                $input['invoice_id'] = $taxYear->invoice;
            }

             $paid = $taxYear->total_paid+$request->paid_amount;
             $payment_status = ($paid==$taxYear->total_amount)?1:0;
             $current_paid = $paid-$taxYear->prev_due;
             $current_paid = ($current_paid<0)?0:$current_paid;

             $input['due_amount'] = $taxYear->total_amount-$paid;
            $collection = TaxCollection::create($input);
            $taxYear->update([
                'last_payment_date'=>$payment_date,
                'total_paid'=>$paid,
                'prev_paid'=>$paid-$current_paid,
                'payment_status'=>$payment_status
            ]);
            return response()->json($collection,201);

        }catch(\Exception $e){
            return response($e->errorInfo[2],500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TaxCollection  $taxCollection
     * @return \Illuminate\Http\Response
     */
    public function show($id,Request $request)
    {

        $holding = TaxCollection::leftJoin('tax_list_yearly','tax_list_id','tax_list_yearly.id')
            ->leftJoin('holdings','holding_id','holdings.id')
            ->leftJoin('holding_type','holdings.type','holding_type.id')
            ->leftJoin('years','tax_list_yearly.year','years.id')
            ->leftJoin('villages','holdings.village_id','villages.id')
            ->select('tax_collections.*','holding_id','holding_no','tax_list_yearly.invoice','organization_name','father_or_husband','owner_name','holdings.type','holdings.word',
                'tax_list_yearly.tax','tax_list_yearly.prev_due','tax_list_yearly.total_amount','tax_list_yearly.discount','tax_list_yearly.total_paid','tax_list_yearly.payment_status','years.name as year_name',
                'holdings.mobile','village_id','villages.bn_name as village_name','holdings.union_id','last_payment_date','mobile','holding_type.name as type_name')
            ->where('holdings.union_id',$request->union_id)
            ->where('tax_collections.id',$id)
            ->first();

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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TaxCollection  $taxCollection
     * @return \Illuminate\Http\Response
     */
    public function edit(TaxCollection $taxCollection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TaxCollection  $taxCollection
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TaxCollection $taxCollection)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required',
            'holding_id' => 'required',
            'tax' => 'required',
            'payment_date' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 403);
        }
        $input = $request->all();
        try{
            $taxCollection->update($input);
            return response()->json('Data Successfully Updated.',201);

        }catch(\Exception $e){
           return response($e->errorInfo[2],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TaxCollection  $taxCollection
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request,$id)
    {

        $holding = TaxCollection::leftJoin('tax_list_yearly','tax_list_id','tax_list_yearly.id')
            ->select('holding_id','tax_list_yearly.total_amount','tax_list_yearly.discount','tax_list_yearly.total_paid','tax_list_yearly.prev_due','tax_list_yearly.prev_paid','tax_list_id')
            ->where(['tax_collections.id'=>$id,'tax_list_yearly.year'=>$request->year])->first();
        if($holding==''){
            return response()->json('Old data.',400);
        }
        $mainData = TaxCollection::leftJoin('tax_list_yearly','tax_list_id','tax_list_yearly.id')->select('tax_collections.id','payment_date')->where('holding_id',$holding->holding_id)->where('tax_collections.id','>',$id)->first();
        if($mainData!=''){
            return response()->json('Old data.',400);
        }else{
            $data = TaxCollection::findOrFail($id);
            $nowPaid = $holding->total_paid- $data->paid_amount;
            $current_paid = $nowPaid-$holding->prev_due;
            $current_paid = ($current_paid<0)?0:$current_paid;
            $prevPaid = $nowPaid - $current_paid;
            $updateData=[
                'total_paid'=>$nowPaid,
                'prev_paid'=> $prevPaid,
                'last_payment_date'=>null,
                'payment_status'=>0
            ];
            $oldData = TaxCollection::leftJoin('tax_list_yearly','tax_list_id','tax_list_yearly.id')->select('tax_collections.id','payment_date')->where('holding_id',$holding->holding_id)->where('tax_collections.id','<',$id)->first();
            if($oldData!=''){
                $updateData['last_payment_date'] =  $oldData->payment_date;
            }
            TaxListYearly::where('id',$data->tax_list_id)->update($updateData);
            $data->delete();
            return response()->json('Data Successfully Delete.',201);
        }
    }
    public function years()
    {
        $data = Years::get();
        return response()->json($data,200);
    }
    public function singleHoldingBill($id,Request $request){
        $holding = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')
            ->leftJoin('holding_type','holdings.type','holding_type.id')
            ->leftJoin('years','tax_list_yearly.year','years.id')
            ->select('tax_list_yearly.id','holding_id','holding_no','tax_list_yearly.invoice','organization_name','father_or_husband','owner_name','holdings.annual_assessment','holdings.type','holdings.word','years.name as year_name',
                'tax_list_yearly.tax','tax_list_yearly.prev_due','tax_list_yearly.total_amount','tax_list_yearly.discount','tax_list_yearly.total_paid','tax_list_yearly.payment_status','tax_list_yearly.year',
                'holdings.mobile','village_id','holdings.union_id','last_payment_date','mobile','holding_type.name as type_name')
            ->where(['tax_list_yearly.year'=>$request->year])
            ->where('tax_list_yearly.id',$id)
            ->first();
        return response()->json($holding, 200);


    }
}

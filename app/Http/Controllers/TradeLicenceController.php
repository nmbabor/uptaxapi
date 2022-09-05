<?php

namespace App\Http\Controllers;

use App\Models\Bazar;
use App\Models\Holdings;
use App\Models\HoldingType;
use App\Models\TaxListYearly;
use App\Models\TaxRate;
use App\Models\TradeLicence;
use App\Models\TradeLicenceFeeYearly;
use App\Models\Union;
use App\Models\UnionBillDetails;
use App\Models\Village;
use App\Models\Years;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator,DB;

class TradeLicenceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $credentials = [];

        $allData = TradeLicence::with('union','bazar')->where('created_by',\Auth::user()->id)->orderBy('trade_licence_no','ASC');
        if($request->keyword!=''){

            $keyword = $request->keyword;
            //$credentials['year'] = $request->keyword;
            $allData = $allData->where(function ($query) use($keyword) {
                $columns = ['holding_no','trade_licence_no','owner_name','organization_name','father_or_husband','mother','mobile'];
                foreach($columns as $column){
                    $query->orWhere($column, 'LIKE', '%' . $keyword . '%');
                }
                return $query;
            });
        }

        if($request->year!=''){
            $credentials['year'] = Years::where('id',$request->year)->value('name');
        }
        if($request->bazar_id!=''){
            $credentials['bazar'] = Bazar::where('id',$request->bazar_id)->value('bn_name');
            $allData = $allData->where('bazar_id',$request->bazar_id);
        }
        if($request->union_id!=''){
            $credentials['union'] = Union::where('id',$request->union_id)->value('bn_name');
            $allData = $allData->where('union_id',$request->union_id);
        }
        $details = UnionBillDetails::where('union_id',$request->union_id)->first();
        $credentials['union_details'] = $details->details;
        $allData = $allData->paginate($request->perpage);
        return response()->json(['allData'=>$allData,'credentials'=>$credentials], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    // Tax Generate
    public function create(Request $request)
    {

        //return response()->json($request->all(),300);
        if(isset($request->invoice_serial) && $request->invoice_serial==2){
            $taxList = TradeLicenceFeeYearly::leftJoin('trade_licence','trade_licence_id','trade_licence.id')->whereNotNull('trade_licence_fee_yearly.id')
                ->where(['union_id'=>$request->union_id,'year'=>$request->year])->orderBy('word','ASC')->orderBy('trade_licence_no','ASC')
                ->select('trade_licence_fee_yearly.id','holding_no','invoice','word')->get();
            $maxId = 0;
            foreach($taxList as $data){
                $maxId++;
                TradeLicenceFeeYearly::where('id',$data->id)->update([
                    'invoice'=>$maxId
                ]);

            }
            return response()->json(['create'=>0,'update'=>$maxId], 200);
        }

        try{

            $check = TradeLicenceFeeYearly::leftJoin('trade_licence','trade_licence_id','trade_licence.id')
                ->where(['union_id'=>$request->union_id])
                ->where('trade_licence_fee_yearly.year','!=',$request->year)->orderBy('trade_licence_fee_yearly.id','DESC')
                ->select('trade_licence_fee_yearly.year')->value('year');


            if($check>$request->year){
                return response()->json('Year Not Eligible', 400);
            }
            $tradeLicences = TradeLicence::where(['union_id'=>$request->union_id])
                ->where(function($query) use ($request){
                    $query->whereNull('current_year')
                        ->orWhere('current_year','!=',$request->year);
                    return $query;
                })
                ->orderBy('word','ASC')->orderBy('trade_licence_no','ASC')
                ->select('annual_tax','tax_due','tax_due_add','id','current_year')->limit(4000)->get();

            $c=0;
            $u=0;
            foreach($tradeLicences as $tradeLicence){
                    $annual_tax = number_format((float)$tradeLicence->annual_tax,2,'.','');
                    $oldData = TradeLicenceFeeYearly::where(['trade_licence_id'=>$tradeLicence->id,'payment_status'=>0])->where('year','!=',$request->year)
                        ->latest()->first();
                    $prevAmount = 0;
                    $totalAmount = $annual_tax;
                    if($oldData!=''){
                        $prevAmount = $oldData->total_amount-$oldData->total_paid??0;
                        $totalAmount +=$prevAmount;
                    }
                    $taxData = TradeLicenceFeeYearly::where(['trade_licence_id'=>$tradeLicence->id,'year'=>$request->year])->first();
                    $maxId = TradeLicenceFeeYearly::leftJoin('trade_licence','trade_licence_id','trade_licence.id')
                            ->whereNotNull('trade_licence_fee_yearly.id')
                            ->where(['union_id'=>$request->union_id,'year'=>$request->year])->count()+1;
                    $invoice = $maxId;
                    if($taxData==''){
                        $taxListYear = [
                            'invoice'=>$invoice,
                            'trade_licence_id'=>$tradeLicence->id,
                            'year'=>$request->year,
                            'tax'=>$annual_tax,
                            'created_by'=>\Auth::user()->id
                        ];
                        if($tradeLicence->tax_due_add==0){
                            $prevAmount+=$tradeLicence->tax_due;
                            $totalAmount+=$tradeLicence->tax_due;
                            $taxListYear['static_due']=$tradeLicence->tax_due;
                        }
                        $taxListYear['prev_due'] = $prevAmount;
                        $taxListYear['total_amount'] = $totalAmount;
                        $create=TradeLicenceFeeYearly::create($taxListYear);
                        $c++;
                    }else{
                        $preAmount = $taxData->prev_due;
                        $totalAmounts = ($preAmount??0)+$annual_tax;
                        $updateData =  [
                            'tax'=>$annual_tax,
                        ];
                        $oldDueStatus = 0;
                        if($tradeLicence->tax_due_add==0){
                            $preAmount+=$tradeLicence->tax_due;
                            $totalAmounts+=$tradeLicence->tax_due;
                            $oldDueStatus = 1;
                        }elseif($taxData->static_due>=0 && $tradeLicence->tax_due!=$taxData->static_due){
                            $preAmount = ($preAmount-$taxData->static_due)+$tradeLicence->tax_due;
                            $totalAmounts = $preAmount+$annual_tax;
                            $oldDueStatus = 1;
                        }
                        $updateData['static_due']=$tradeLicence->tax_due;
                        $updateData['prev_due']=$preAmount;
                        $updateData['total_amount']=$totalAmounts;
                        $taxDataTax = number_format((float)$taxData->tax,2,'.','');
                        if($taxDataTax!=$annual_tax || $oldDueStatus == 1){
                            $taxData->update($updateData);
                            $u++;
                        }
                    }
                    if($tradeLicence->tax_due_add==0){
                        $tradeLicence->update(['tax_due_add'=>1]);
                    }
                    if($oldData!=''){
                        $oldData->update(['payment_status'=>1]);
                    }
                    $tradeLicence->update(['current_year'=>$request->year]);
            }

            return response()->json(['create'=>$c,'update'=>$u,'total'=>count($tradeLicences)], 200);
        }catch(\Exception $e){

            return response($e->errorInfo[1],500);

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
        $trade_licence_no = $request->trade_licence_no;
        $union_id = $request->union_id;
        $validator = Validator::make($request->all(), [
            'holding_no'=> 'required',
            'trade_licence_no'=> [
                'required',
                Rule::unique('trade_licence')->where(function ($query) use($trade_licence_no,$union_id) {
                    return $query->where('trade_licence_no', $trade_licence_no)
                       ->where('union_id', $union_id);
                }),
            ],
            'owner_name'=> 'required',
            'father_or_husband'=> 'required',
            'mobile'=> 'required',
            'gender'=> 'required',
            'nid'=> 'required',
            'union_id'=> 'required',
            'word'=> 'required',
            'bazar_id' => 'required',
            'annual_tax' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 403);
        }
        $input = $request->all();

        try{
            $input['created_by'] = \Auth::user()->id;
            TradeLicence::create($input);

            return response()->json('Data Successfully Inserted.',201);

        }catch(\Exception $e){
            $bug=$e->errorInfo[1];
            if($bug==1062){
                return response()->json('Data has already been taken.', 303);
            }else{
                return response($e->errorInfo[2],500);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Holdings  $holdings
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$id)
    {
        $data = TradeLicence::findOrFail($id);
        $allData = [
            'tradeLicence'=>$data,
            'tax'=>'',
            'area'=>'',
        ];
        if(isset($request->year)){
            $tax = TradeLicenceFeeYearly::leftJoin('trade_licence','trade_licence_id','trade_licence.id')
                ->leftJoin('years','trade_licence_fee_yearly.year','years.id')
                ->leftJoin('bazars','trade_licence.bazar_id','bazars.id')
                ->select('trade_licence_fee_yearly.id','holding_no','trade_licence_fee_yearly.invoice','organization_name','father_or_husband','owner_name',
                    'trade_licence_fee_yearly.tax','trade_licence_fee_yearly.prev_due','trade_licence_fee_yearly.total_amount','trade_licence_fee_yearly.discount','trade_licence_fee_yearly.total_paid','trade_licence_fee_yearly.payment_status','years.name as year_name',
                    'trade_licence.mobile','bazar_id','bazars.bn_name as bazar_name','trade_licence.union_id','last_payment_date')
                ->where(['trade_licence_fee_yearly.year'=>$request->year])
                ->where('trade_licence_id',$data->id)
               ->orderBy('trade_licence_no','ASC')
                ->first();
            $area = Union::leftJoin('upazilas','upazila_id','upazilas.id')
                ->leftJoin('districts','district_id','districts.id')
                ->where('unions.id',$data->union_id)
                ->select('districts.bn_name as district_name','upazilas.bn_name as upazila_name','unions.bn_name as union_name')->first();
            $allData['tax'] = $tax;
            $allData['area'] = $area;
        }

        return response()->json($allData,200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Holdings  $holdings
     * @return \Illuminate\Http\Response
     */
    public function edit(Holdings $holdings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Holdings  $holdings
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Holdings $holdings,$id)
    {
        //return response()->json('Discount Successfully Added.',200);
        if(isset($request->holding_no)){
            $trade_licence_no = $request->trade_licence_no;
            $union_id = $request->union_id;
            $validator = Validator::make($request->all(), [
                'holding_no'=> 'required',
                'trade_licence_no'=> [
                    'required',
                    Rule::unique('trade_licence')->where(function ($query) use($trade_licence_no,$union_id,$id) {
                        return $query->where('trade_licence_no', $trade_licence_no)
                            ->where('union_id', $union_id)->where('id','!=',$id);
                    }),
                ],
                'owner_name'=> 'required',
                'father_or_husband'=> 'required',
                'mobile'=> 'required',
                'gender'=> 'required',
                'nid'=> 'required',
                'union_id'=> 'required',
                'word'=> 'required',
                'bazar_id' => 'required',
                'annual_tax' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error'=>$validator->errors()], 403);
            }
            $input = $request->all();

            try{

                $input['current_year']=null;
                $data = TradeLicence::findOrFail($id);
                $data->update($input);

                return response()->json('Data Successfully Updated.',201);

            }catch(\Exception $e){
                $bug=$e->errorInfo[1];
                if($bug==1062){
                    return response()->json('Data has already been taken.', 303);
                }else{
                    return response($e->errorInfo[2],500);
                }
            }
        }else if(isset($request->discount_amount)){
            $tax = TradeLicenceFeeYearly::findOrFail($request->tax_year_id);
            $mainAmount = $tax->prev_due+$tax->tax;
            $totalAmount = $mainAmount-$request->discount_amount;
            $tax->update(['discount'=>$request->discount_amount,'total_amount'=>$totalAmount]);
            return response()->json('Discount Successfully Added.',200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Holdings  $holdings
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        DB::beginTransaction();
        try {
            $data = TradeLicence::findOrFail($id);
           $yearly = TradeLicenceFeeYearly::where('trade_licence_id',$id)->get();
           if(count($yearly)>0){
               TradeLicenceFeeYearly::where('trade_licence_id',$id)->delete();
           }
            $data->delete();
            $bug = 0;
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            $bug = $e->errorInfo[1];
            $bug1 = $e->errorInfo[2];
        }

        if ($bug == 0) {
            return response()->json('Data Successfully Delete.',201);
        }elseif ($bug==1451){
            return response()->json('This data is used anywhere!',400);
        }
        else {
            return response()->json('Something Error Found! ' . $bug1,400);
        }

    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Holdings;
use App\Models\HoldingType;
use App\Models\Union;
use App\Models\TaxRate;
use App\Models\TaxListYearly;
use App\Models\UnionBillDetails;
use App\Models\Years;
use App\User;
use Illuminate\Http\Request;
use Validator,DB;
use Illuminate\Validation\Rule;
use App\Models\Village;

class HoldingsController extends Controller
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
      $allData = Holdings::with('union','village')->where('created_by',\Auth::user()->id)->orderBy('word','ASC')->orderBy('holding_no','ASC');
      if($request->keyword!=''){

            $keyword = $request->keyword;
            //$credentials['year'] = $request->keyword;
            $allData = $allData->where(function ($query) use($keyword) {
                  $columns = ['holding_no','owner_name','organization_name','father_or_husband','mother','mobile'];
                  foreach($columns as $column){
                        $query->orWhere($column, 'LIKE', '%' . $keyword . '%');
                }
                return $query;
            });
    }
      if($type!=0){
           $credentials['type'] = HoldingType::where('sl_no',$type)->value('name');
          $allData = $allData->where('type',$type);
      }
      if($request->year!=''){
          $credentials['year'] = Years::where('id',$request->year)->value('name');
      }
      if($request->village_id!=''){
           $credentials['village'] = Village::where('id',$request->village_id)->value('bn_name');
           $allData = $allData->where('village_id',$request->village_id);
      }
      if($request->union_id!=''){
           $credentials['union'] = Union::where('id',$request->union_id)->value('bn_name');
           $allData = $allData->where('union_id',$request->union_id);
      }
      if($request->word!=''){
           $words = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
           $credentials['word'] = $words[$request->word];
           $allData = $allData->where('union_id',$request->union_id)->where('word',$request->word);
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
    // Tax Generate , tax-generate
    public function create(Request $request)
    {

        if(isset($request->invoice_serial) && $request->invoice_serial==2){
            $taxList = TaxListYearly::leftJoin('holdings','tax_list_yearly.holding_id','holdings.id')->whereNotNull('tax_list_yearly.id')
                ->where(['union_id'=>$request->union_id,'type'=>$request->type,'year'=>$request->year])->orderBy('word','ASC')->orderBy('holding_no','ASC')
                ->select('tax_list_yearly.id','holding_no','invoice','word')->get();
            $maxId = 0;
            foreach($taxList as $data){
                $maxId++;
                TaxListYearly::where('id',$data->id)->update([
                    'invoice'=>$maxId
                ]);

            }
            return response()->json(['create'=>0,'update'=>$maxId], 200);
        }

        try{
        $tax = TaxRate::where(['year_id'=>$request->year,'type'=>$request->type])->first();
            if($tax==''){
                return response()->json('No Tax rate found in this year', 400);
            }
        $check = TaxListYearly::leftJoin('holdings','tax_list_yearly.holding_id','holdings.id')
            ->where(['union_id'=>$request->union_id,'type'=>$request->type])
        ->where('tax_list_yearly.year','!=',$request->year)->orderBy('tax_list_yearly.id','DESC')->select('tax_list_yearly.year')->value('year');


            if($check>$request->year){
                 return response()->json('Year Not Eligible', 400);
            }
        $holdings = Holdings::where(['union_id'=>$request->union_id,'type'=>$request->type])
         ->where(function($query) use ($request){
             $query->whereNull('current_year')
                 ->orWhere('current_year','!=',$request->year);
             return $query;
         })
         ->orderBy('word','ASC')->orderBy('holding_no','ASC')
         ->select('annual_assessment','tax_due','tax_due_add','id','current_year')->limit(4000)->get();
        $c=0;
        $u=0;
        foreach($holdings as $holding){
            if($tax!=''){
                $monthly_bill = ($holding->annual_assessment/12);
                $bill = $monthly_bill*10;
                $annual_tax = ($bill/100)*$tax->tax;
                $annual_tax = number_format((float)$annual_tax,2,'.','');
                $oldData = TaxListYearly::where(['holding_id'=>$holding->id,'payment_status'=>0])->where('year','!=',$request->year)->latest()->first();
                $prevAmount = 0;
                $totalAmount = $annual_tax;
                if($oldData!=''){
                    $prevAmount = $oldData->total_amount-$oldData->total_paid??0;
                    $totalAmount +=$prevAmount;
                }
                $taxData = TaxListYearly::where(['holding_id'=>$holding->id,'year'=>$request->year])->first();
                 $maxId = TaxListYearly::leftJoin('holdings','tax_list_yearly.holding_id','holdings.id')->whereNotNull('tax_list_yearly.id')->where(['union_id'=>$request->union_id,'type'=>$request->type,'year'=>$request->year])->count()+1;
                 $invoice = $maxId;
                if($taxData==''){
                    $taxListYear = [
                        'invoice'=>$invoice,
                        'holding_id'=>$holding->id,
                        'year'=>$request->year,
                        'tax'=>$annual_tax,
                        'created_by'=>\Auth::user()->id
                    ];
                    if($holding->tax_due_add==0){
                        $prevAmount+=$holding->tax_due;
                        $totalAmount+=$holding->tax_due;
                        $taxListYear['static_due']=$holding->tax_due;
                    }
                    $taxListYear['prev_due'] = $prevAmount;
                    $taxListYear['total_amount'] = $totalAmount;
                    $create=TaxListYearly::create($taxListYear);
                    $c++;
                }else{
                    $preAmount = $taxData->prev_due;
                    $totalAmounts = ($preAmount??0)+$annual_tax;
                    $updateData =  [
                        'tax'=>$annual_tax,
                    ];
                    $oldDueStatus = 0;
                    if($holding->tax_due_add==0){
                        $preAmount+=$holding->tax_due;
                        $totalAmounts+=$holding->tax_due;
                        $oldDueStatus = 1;
                    }elseif($taxData->static_due>=0 && $holding->tax_due!=$taxData->static_due){
                        $preAmount = ($preAmount-$taxData->static_due)+$holding->tax_due;
                        $totalAmounts = $preAmount+$annual_tax;
                        $oldDueStatus = 1;
                    }
                    $updateData['static_due']=$holding->tax_due;
                    $updateData['prev_due']=$preAmount;
                    $updateData['total_amount']=$totalAmounts;
                    $taxDataTax = number_format((float)$taxData->tax,2,'.','');
                    if($taxDataTax!=$annual_tax || $oldDueStatus == 1){
                        $taxData->update($updateData);
                        $u++;
                    }
                }
                if($holding->tax_due_add==0){
                    $holding->update(['tax_due_add'=>1]);
                }
                if($oldData!=''){
                    $oldData->update(['payment_status'=>1]);
                }
                $holding->update(['current_year'=>$request->year]);
            }
        }

        return response()->json(['create'=>$c,'update'=>$u,'total'=>count($holdings)], 200);
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
        $holding_no = $request->holding_no;
        $type = $request->type;
        $union_id = $request->union_id;
        $validator = Validator::make($request->all(), [
            'type'=> 'required',
            'holding_no'=> [
                    'required',
                    Rule::unique('holdings')->where(function ($query) use($holding_no,$type,$union_id) {
                        return $query->where('holding_no', $holding_no)
                        ->where('type', $type)->where('union_id', $union_id);
                    }),
                ],
            'owner_name'=> 'required',
            'father_or_husband'=> 'required',
            'mother'=> 'required',
            'mobile'=> 'required',
            'religion'=> 'required',
            'gender'=> 'required',
            'birthday'=> 'required',
            'nid'=> 'required',
            'union_id'=> 'required',
            'word'=> 'required',
            'village_id' => 'required',
            'annual_assessment' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 403);
        }
        $input = $request->all();

        try{
            $input['annual_tax'] = 0;
            $currentYearId = Years::where('current_year',1)->value('id');

        $tax = TaxRate::where(['year_id'=>$currentYearId,'type'=>$type])->first();
        if($tax!==''){
            $monthly_bill = ($request->annual_assessment/12);
            $bill = $monthly_bill*10;
            $input['annual_tax'] = ($bill/100)*$tax->tax;
        }
        $input['created_by'] = \Auth::user()->id;
        $birthday = \substr($input['birthday'],0,10);
        $input['birthday'] = date('Y-m-d',strtotime($birthday));
        $user = Holdings::create($input);

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
        $data = Holdings::findOrFail($id);
        $allData = [
            'holding'=>$data,
            'tax'=>'',
            'area'=>'',
        ];
        if(isset($request->year)){
            $tax = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')
                ->leftJoin('years','tax_list_yearly.year','years.id')
                ->leftJoin('holding_type','holdings.type','holding_type.id')
                ->leftJoin('villages','holdings.village_id','villages.id')
                ->select('tax_list_yearly.id','holding_id','holding_no','tax_list_yearly.invoice','organization_name','father_or_husband','owner_name','holdings.type','holdings.word',
                    'tax_list_yearly.tax','tax_list_yearly.prev_due','tax_list_yearly.total_amount','tax_list_yearly.discount','tax_list_yearly.total_paid','tax_list_yearly.payment_status','years.name as year_name',
                    'holdings.mobile','village_id','villages.bn_name as village_name','holdings.union_id','last_payment_date','mobile','holding_type.name as type_name')
                ->where(['tax_list_yearly.year'=>$request->year])
                ->where('holding_id',$data->id)
                ->orderBy('word','ASC')->orderBy('holding_no','ASC')
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
            $holding_no = $request->holding_no;
            $type = $request->type;
            $union_id = $request->union_id;
            $validator = Validator::make($request->all(), [
                'type'=> 'required',
                'holding_no'=> [
                    'required',
                    Rule::unique('holdings')->where(function ($query) use($holding_no,$type,$union_id,$id) {
                        return $query->where('holding_no', $holding_no)
                            ->where('type', $type)->where('union_id', $union_id)->where('id','!=',$id);
                    }),
                ],
                'owner_name'=> 'required',
                'father_or_husband'=> 'required',
                'mother'=> 'required',
                'mobile'=> 'required',
                'religion'=> 'required',
                'gender'=> 'required',
                'birthday'=> 'required',
                'nid'=> 'required',
                'union_id'=> 'required',
                'word'=> 'required',
                'village_id' => 'required',
                'annual_assessment' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error'=>$validator->errors()], 403);
            }
            $input = $request->all();

            try{
                $input['annual_tax'] = 0;
                $currentYearId = Years::where('current_year',1)->value('id');
                $tax = TaxRate::where(['year_id'=>$currentYearId,'type'=>$type])->first();
                if($tax!==''){
                    $monthly_bill = ($request->annual_assessment/12);
                    $bill = $monthly_bill*10;
                    $input['annual_tax'] = ($bill/100)*$tax->tax;
                }
                $birthday = \substr($input['birthday'],0,10);
                $input['birthday'] = date('Y-m-d',strtotime($birthday));
                $input['current_year']=null;
                $data = Holdings::findOrFail($id);
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
            $tax = TaxListYearly::findOrFail($request->tax_year_id);
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
    public function destroy(Holdings $holdings,$id)
    {

        DB::beginTransaction();
        try {
            $data = Holdings::findOrFail($id);
            TaxListYearly::where('holding_id',$id)->delete();
            $data->delete();
            $bug = 0;
            DB::commit();

        } catch (\Exception $e) {
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
    public function unions()
    {
        $user = User::findOrFail(\Auth::user()->id);
        $unions = [];
        foreach($user->unions as $union){
            $unions[]=Union::findOrFail($union->union_id);
        }
        return response()->json($unions, 201);
    }

    public function holdingType()
    {
        $data = HoldingType::whereStatus(1)->orderBy('sl_no','ASC')->pluck('name','sl_no');
        return response()->json($data, 201);
    }
}

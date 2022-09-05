<?php

namespace App\Http\Controllers;

use App\Models\UnionBillDetails;
use Illuminate\Http\Request;
use Validator;

class UnionBillDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $allData = UnionBillDetails::with('union')->get();
        return response()->json($allData, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'union_id'=>'required',
            'chairman_name'=>'required',
            'chairman_mobile'=>'required',
            'bill_start_date'=>'required',
            'bill_end_date'=>'required',

        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 403);
        }
        $input = $request->all();

        try{
        $input['created_by'] = \Auth::user()->id;
        $bill_start_date = \substr($input['bill_start_date'],0,10);
        $input['bill_start_date'] = date('Y-m-d',strtotime($bill_start_date));
        $bill_end_date = \substr($input['bill_end_date'],0,10);
        $input['bill_end_date'] = date('Y-m-d',strtotime($bill_end_date));
        if ($request->hasFile('signature')){
            $input['signature']=\MyHelper::fileUpload($request->file('signature'),'images/signature/');
        }
        if ($request->hasFile('logo')){
            $input['logo']=\MyHelper::fileUpload($request->file('logo'),'images/logo/');

        }

        $data = UnionBillDetails::where('union_id',$request->union_id)->first();
        if($data!=''){
             if ($request->hasFile('signature')){
                if(file_exists($data->signature)){
                    unlink($data->signature);
                }
             }
             if ($request->hasFile('logo')){
                if(file_exists($data->logo)){
                    unlink($data->logo);
                }
             }
            $data->update($input);
        }else{
            $user = UnionBillDetails::create($input);
        }

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
     * @param  \App\Models\UnionBillDetails  $unionBillDetails
     * @return \Illuminate\Http\Response
     */
    public function show(UnionBillDetails $unionBillDetails,$id)
    {
        $data = UnionBillDetails::where('union_id',$id)->first();
        return response()->json($data,201);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UnionBillDetails  $unionBillDetails
     * @return \Illuminate\Http\Response
     */
    public function edit(UnionBillDetails $unionBillDetails)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UnionBillDetails  $unionBillDetails
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UnionBillDetails $unionBillDetails)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UnionBillDetails  $unionBillDetails
     * @return \Illuminate\Http\Response
     */
    public function destroy(UnionBillDetails $unionBillDetails)
    {
        //
    }
}

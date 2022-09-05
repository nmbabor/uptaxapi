<?php

namespace App\Http\Controllers;

use App\Models\Union;
use Illuminate\Http\Request;
use Validator;

class UnionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
            'bn_name' => 'required',
            'upazila_id' => 'required',
        ]);   
        if ($validator->fails()) {          
            return response()->json(['error'=>$validator->errors()], 403);
        }    
        
         try{
            $input = $request->except('division_id','district_id');
        
        $user = Union::create($input);
        return response()->json('Data Successfully Inserted.',201); 
        }catch(\Exception $e){
            return response($e->errorInfo[2],500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Union  $union
     * @return \Illuminate\Http\Response
     */
    public function show(Union $union)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Union  $union
     * @return \Illuminate\Http\Response
     */
    public function edit(Union $union)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Union  $union
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Union $union)
    {
        $validator = Validator::make($request->all(), [ 
            'bn_name' => 'required',
        ]);   
        if ($validator->fails()) {          
            return response()->json(['error'=>$validator->errors()], 403);
        }    
        $input = $request->all();  
        try{
            $union->update($input);
            return response()->json('Data Successfully Updated.',201);  
            
        }catch(\Exception $e){
           return response($e->errorInfo[2],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Union  $union
     * @return \Illuminate\Http\Response
     */
    public function destroy(Union $union)
    {
        //
    }
}

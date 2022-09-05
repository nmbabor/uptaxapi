<?php

namespace App\Http\Controllers;

use App\Models\Holdings;
use App\Models\TaxList;
use Illuminate\Http\Request;

class TaxListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Holdings::whereRaw('LENGTH(mobile)<11')->where('mobile','!=',0)->select('id','mobile')
            ->where('created_by',7)->get();

        foreach ($data as $dt){
            $firstdigit =  substr($dt->mobile,0,1);
            if($firstdigit!=0){
                $mobile = '0'.$dt->mobile;
                Holdings::where('id',$dt->id)->update(['mobile'=>$mobile]);
            }
        }
        return $data;
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TaxList  $taxList
     * @return \Illuminate\Http\Response
     */
    public function show(TaxList $taxList)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TaxList  $taxList
     * @return \Illuminate\Http\Response
     */
    public function edit(TaxList $taxList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TaxList  $taxList
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TaxList $taxList)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TaxList  $taxList
     * @return \Illuminate\Http\Response
     */
    public function destroy(TaxList $taxList)
    {
        //
    }
}

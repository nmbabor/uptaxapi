<?php
namespace App\Http\Controllers;

use App\Models\Bazar;
use App\Models\Union;
use App\Models\Division;
use App\Models\District;
use App\Models\Upazila;
use App\Models\Village;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function divisions(){
        $allData = Division::get();
        return response()->json($allData, 200);
    }
    public function districts($id){
        $allData = District::where('division_id',$id)->get();
        return response()->json($allData, 200);
    }
    public function upazila($id){
        $allData = Upazila::where('district_id',$id)->get();
        return response()->json($allData, 200);
    }
    public function unions($id){
        $allData = Union::where('upazila_id',$id)->get();
        return response()->json($allData, 200);
    }
    public function villages($id){
        $allData = Village::where('union_id',$id)->get();
        return response()->json($allData, 200);
    }
    public function bazars($id){
        $allData = Bazar::where('union_id',$id)->get();
        return response()->json($allData, 200);
    }

}

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Models\UserUnion;
use App\Models\Years;
use App\Models\TaxListYearly;

class AuthController extends Controller
{
    public $successStatus = 200;

    public function login(Request $request){

        try{

            if(Auth::attempt(['email' => request('email'), 'password' => request('password'),'status'=>1])){
                $user = Auth::user();
                $success['token'] =  $user->createToken('AppName')->accessToken;
                $success['id'] = $user->id;
                $success['name'] = $user->name;
                $success['email'] = $user->email;
                $success['type'] = $user->type;
                $union = UserUnion::leftJoin('unions','union_id','unions.id')->where('user_id',$user->id)->select('union_id','bn_name')->first();
                $success['union_id'] = $union->union_id;
                $success['union_name'] = $union->bn_name;
                $year = TaxListYearly::leftJoin('holdings','holding_id','holdings.id')->where('holdings.union_id',$union->union_id)
        ->orderBy('tax_list_yearly.year','DESC')->select('tax_list_yearly.year')->value('year');
                if($year==''){
                   $year = Years::where('current_year',1)->value('id');
                }
                $success['year'] = $year;

                return response()->json($success, 200);
            } else{
                return response()->json(['error'=>'Unauthorised'], 401);
            }

        }catch(\Exception $e){
            $bug=$e->errorInfo[1];
            return response($e->errorInfo[2],500);
        }

    }
    public function getUser() {
        $user = Auth::user();
        return response()->json($user, 200);
    }

}

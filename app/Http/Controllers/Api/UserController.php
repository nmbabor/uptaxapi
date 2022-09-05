<?php

namespace App\Http\Controllers\Api;

use App\Models\UserPermission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use App\User;
use App\Models\UserUnion;
use App\Models\Union;
use App\Models\Upazila;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::whereNotIn('email',['admin@codeplanners.com','nmbabor50@gmail.com'])->get();
        return response()->json($users, 200);
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

        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|unique:users',
                'mobile' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);
            if ($validator->fails()) {
                return response()->json(['error'=>$validator->errors()], 403);
            }
            $input = $request->all();
           // return response($request->all(),500);
            $input['password'] = bcrypt($input['password']);
            $user = User::create($input);
            if(isset($request->unions[0])){
                foreach($request->unions as $value){
                    UserUnion::create([
                        'user_id'=>$user->id,
                        'union_id'=>$value['value'],
                    ]);
                }
            }else{
                UserUnion::create([
                    'user_id'=>$user->id,
                    'union_id'=>$request->unions['value'],
                ]);
            }

            return response()->json('Data Successfully Inserted.',201);

        }catch(Exception $e){
            $bug=$e->errorInfo[1];
            if($bug==1062){
                return response()->json('The Email has already been taken.', 303);
            }else{
                return response($e->errorInfo[2],500);
            }
        }


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        try{
            $user = User::findOrFail($id);
            $unions = [];

            foreach($user->unions as $union){
                $uni = Union::findOrFail($union->union_id);
                $unions[]=["label"=>$uni->bn_name,"value"=>$uni->id];
            }
            if(isset($uni)){
                $upazila = Upazila::with('district')->findOrFail($uni->upazila_id);
                $user["upazila_id"]=$upazila->id;
                $user["district_id"]=$upazila->district->id;
                $user["division_id"]=$upazila->district->division_id;
            }


            return response()->json(['user'=>$user,'unions'=>$unions],200);

         }catch(\Exception $e){
            $bug=$e->errorInfo[1];
            if($bug>0){
                return response($e->errorInfo[2],500);
            }
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => "required|email|unique:users,email,$id",
            'mobile' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 403);
        }
        $input = $request->all();

        try{
            $user = User::findOrFail($id);
            $user->update($input);
            UserUnion::where('user_id',$id)->delete();
            if(isset($request->unions[0])){
                foreach($request->unions as $value){
                    UserUnion::create([
                        'user_id'=>$user->id,
                        'union_id'=>$value['value'],
                    ]);
                }
            }else{
                UserUnion::create([
                    'user_id'=>$user->id,
                    'union_id'=>$request->unions['value'],
                ]);
            }

            return response()->json('Data Successfully Updated.',201);

        }catch(\Exception $e){
            $bug=$e->errorInfo[1];
            if($bug==1062){
                return response()->json('The Email has already been taken.', 303);
            }else{
                return response($e->errorInfo[2],500);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
      public function password(Request $request){
        $input=$request->all();
        $newPass=$input['password'];
        $data=User::findOrFail($request->id);
        $validator = Validator::make($request->all(),[
            'password' => 'required|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 403);
        }
        $input['password']=bcrypt($newPass);
        try{
            $data->update($input);
            return response()->json('Password Successfully Updated.',200);
        }catch(\Exception $e){
            $bug=$e->errorInfo[1];
            return response($e->errorInfo[2],500);
        }

    }

    public function userPermission(){
        $permissions = UserPermission::where('user_id',\Auth::user()->id)->pluck('permission');
        return response()->json($permissions,200);

    }
}

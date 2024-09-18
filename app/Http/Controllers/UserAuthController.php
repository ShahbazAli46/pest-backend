<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserAuthController extends Controller
{
    public function login(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'email'=>'required|string|email',
			'password'=>'required|min:8|max:15'
		]);
	
		if($validator->fails()) {
			return response()->json(['status' => 'error','message' => $validator->errors()->first()],422);
		}

		$user = User::with('role:id,name')->where('email',$request->email)->first();
		if(!$user || !Hash::check($request->password,$user->password)){
			return response()->json([
				'status' => 'error',
				'message' => 'Invalid Credentials'
			],401);
		}
		$token = $user->createToken($user->name.'-AuthToken')->plainTextToken;
		$rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id", $user->role_id)->get();
		$permiss=[];
		$i=0;
		foreach ($rolePermissions as $rolePermission) {
			$data = Permission::where('id', $rolePermission->permission_id)->first();
			$permiss['permission'][$i] = [
				"name" => $data->name,
				"api_url" => $data->api_url,
				"frontend_url" => $data->frontend_url,
				"parent_id" => $data->parent_id,
				"is_main" => $data->is_main,
				"icon" => env('APP_URL').'/upload/icons/'.$data->icon
			];
			$i++;
		}

		return response()->json(['status' => 'success','token' => $token,'data' => $user,'permission' => $permiss]);
    }
  

    public function logout(){
	    auth()->user()->tokens()->delete();
	    return response()->json([
		  'status' => 'success',
	      "message"=>"Logged Out Successfully"
	    ]);
	}
}

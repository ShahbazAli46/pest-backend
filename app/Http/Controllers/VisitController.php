<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    //Get
    // public function index($id=null){
    //     if($id==null){
    //         $branches=Branch::orderBy('id', 'DESC')->get();
    //         return response()->json(['data' => $branches]);
    //     }else{
    //         $branch=Branch::find($id);
    //         return response()->json(['data' => $branch]);
    //     }
    // }

    // protected $fillable = ['user_id','employee_id','client_id','description','status','current_contract_end_date','visit_date'];
    
    //Store
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([    
                'user_id' => 'required|exists:users,id', 
                'description' => 'nullable|string',
                'status' => 'required|string|in:Interested,Not-Interested,Contracted',
                'current_contract_end_date' => 'nullable|date|required_if:status,Contracted',
                'visit_date' => 'required|string',
                'client_id' => 'required|exists:users,id', 
                'latitude' => 'required|string',
                'longitude' => 'required|string',
            ]);

            $client_user=User::where('role_id',5)->where('id',$request->client_id)->first();
            if(!$client_user){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The specified user does not have the Client.'], 400);
            }

            $user=User::with(['employee'])->whereIn('role_id',[8,9])->where('id',$request->user_id)->first();
            if(!$user){
                DB::rollBack();    
                return response()->json(['status' => 'error','message' => 'The specified user does not have valid Role.'], 400);
            }

            $validateData['employee_id']=$user->employee->id;

            $visit=Visit::create($validateData);
            if($visit){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Visit Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Branch,Please Try Again Later.'],500);
            }
            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Branch. ' .$e->getMessage()],500);
        }
    }


}

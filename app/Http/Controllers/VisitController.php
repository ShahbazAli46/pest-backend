<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Visit;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    use GeneralTrait;

    //Get
    // public function index($id=null){
    //     if($id==null){
    //         $visits=Visit::orderBy('id', 'DESC')->get();
    //         return response()->json(['data' => $visits]);
    //     }else{
    //         $visit=Visit::find($id);
    //         return response()->json(['data' => $visit]);
    //     }
    // }

    //Store
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([    
                'user_id' => 'required|exists:users,id', 
                'description' => 'nullable|string',
                'status' => 'required|string|in:Interested,Not-Interested,Contracted,Quoted',
                'current_contract_end_date' => 'nullable|date|required_if:status,Contracted',
                'visit_date' => 'required|string',
                'user_client_id' => 'required|exists:users,id', 
                'latitude' => 'required|string',
                'longitude' => 'required|string',
                'follow_up_date' => 'nullable|date',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120'
            ]);

            $client_user=User::where('role_id',5)->where('id',$request->user_client_id)->first();
            if(!$client_user){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The specified user does not have the Client.'], 400);
            }

            $uploadedImages = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $uploadedImages[] = $this->saveImage($image, 'visits');
                }
            }
            $validateData['images'] = $uploadedImages;

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

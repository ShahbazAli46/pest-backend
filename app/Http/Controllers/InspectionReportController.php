<?php

namespace App\Http\Controllers;

use App\Models\InspectionReport;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspectionReportController extends Controller
{
    //
    use GeneralTrait;

    //Get
    public function index(Request $request,$id=null){
        if($id==null){
            $inspection_report_query=InspectionReport::with('userClient');

            if ($request->has('user_client_id')) {
                $inspection_report_query->where('user_client_id', $request->input('user_client_id'));
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
                $inspection_reports=$inspection_report_query->whereBetween('created_at', [$startDate, $endDate])->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $inspection_reports]);
            }else{
                $inspection_reports=$inspection_report_query->get();
                return response()->json(['data' => $inspection_reports]);
            }
        }else{
            $inspection_reports=InspectionReport::with('userClient')->where('id',$id)->first();
            return response()->json(['data' => $inspection_reports]);
        }
    }

    //Store
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([    
                'user_id' => 'required|exists:users,id', 
                'client_remarks' => 'nullable|string',
                'inspection_remarks' => 'nullable|string',
                'recommendation_for_operation' => 'nullable|string',
                'general_comment' => 'nullable|string',
                'nesting_area' => 'nullable|string',
                'user_client_id' => 'required|exists:users,id', 
                'pictures.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120'
            ]);

            $client_user=User::where('role_id',5)->where('id',$request->user_client_id)->first();
            if(!$client_user){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The specified user does not have the Client.'], 400);
            }                                                       

            $uploadedImages = [];
            if ($request->hasFile('pictures')) {
                foreach ($request->file('pictures') as $image) {
                    $uploadedImages[] = $this->saveImage($image, 'visits');
                }
            }
            $validateData['pictures'] = $uploadedImages;

            $user=User::with(['employee'])->whereIn('role_id',[10])->where('id',$request->user_id)->first();
            if(!$user){
                DB::rollBack();    
                return response()->json(['status' => 'error','message' => 'The specified user does not have valid Role.'], 400);
            }

            $validateData['employee_id']=$user->employee->id;

            $inspection_report=InspectionReport::create($validateData);
            if($inspection_report){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Inspection Report Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Inspection Report,Please Try Again Later.'],500);
            }
            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Inspection Report. ' .$e->getMessage()],500);
        }
    }

}

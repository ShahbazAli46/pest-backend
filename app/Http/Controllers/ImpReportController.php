<?php

namespace App\Http\Controllers;

use App\Models\ImpReports;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImpReportController extends Controller
{
    use GeneralTrait;

    //Get
    public function index(Request $request,$user_client_id=null){
        $imp_reports=ImpReports::with(['userClient.client'])->orderBy('id', 'DESC');
        
        if($request->has('user_client_id')){
            $imp_reports->where('user_client_id',$user_client_id);
        }

        if($request->has('start_date') && $request->has('end_date')){
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
            $imp_reports=$imp_reports->whereBetween('report_date', [$startDate, $endDate])->get();
            return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $imp_reports]);
        }

        return response()->json(['data' => $imp_reports]);
    }

    //Store
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([    
                'user_client_id' => 'required|exists:users,id', 
                'job_id' => 'required|exists:jobs,id', 
                'report_date' => 'required|date',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
                'description' => 'nullable|string',
            ]);

            $client_user=User::where('role_id',5)->where('id',$request->user_client_id)->first();
            if(!$client_user){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The specified user does not have the Client.'], 400);
            }

            $uploadedImages = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $uploadedImages[] = $this->saveImage($image, 'imp_reports');
                }
            }
            $validateData['images'] = $uploadedImages;
            $imp_report=ImpReports::create($validateData);
            if($imp_report){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'IMP Report Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add IMP Report,Please Try Again Later.'],500);
            }
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add IMP Report. ' .$e->getMessage()],500);
        }
    }
}

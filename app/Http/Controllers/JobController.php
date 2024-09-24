<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Traits\GeneralTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    use GeneralTrait;
    //
    public function index($id)
    {
        $is_int = filter_var($id, FILTER_VALIDATE_INT);
        $type=$id;
        if ($is_int === false) {
            if($type=='pending' || $type=='completed'){
                $is_completed=$type=='pending'?0:1;
                $jobs = Job::with(['user.client.referencable'])
                ->where('is_completed',$is_completed)
                ->orderBy('id', 'DESC')->get();
                // ->map(function ($job) {
                //     $job->treatment_methods = $job->getTreatmentMethods();    
                //     return $job; 
                // });
            }else {
                $jobs = Job::with(['user.client.referencable'])
                ->orderBy('id', 'DESC')->get();
                // ->map(function ($job) {
                //     $job->treatment_methods = $job->getTreatmentMethods(); 
                //     return $job; 
                // });
            }
            return response()->json(['type'=>$type,'data' => $jobs]);
        }else{
            $job = Job::with(['user.client.referencable', 'termAndCondition', 'jobServices.service'])->find($id);
            if ($job) {
                $job->treatment_methods = $job->getTreatmentMethods();
                $job->team_members = $job->getTeamMembers(); 
            }
            return response()->json(['data' => $job]);
        }           
    }

    public function store(Request $request){
        $request->merge([
            'service_ids' => explode(',', $request->input('service_ids')),
            'service_rates' => explode(',', $request->input('service_rates')),
            'tm_ids' => explode(',', $request->input('tm_ids')),
        ]);
        return $this->createJob($request);
    }

    public function rescheduleJob(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([     
                'job_id' => 'required|exists:jobs,id',
                'job_date' => 'required|date',
            ]);

            $job=Job::find($request->job_id);
            if($job){
                if($job->is_completed==0){
                    if($job->job_date!=$request->job_date){
                        $job->update(['job_date'=>$request->job_date,'is_modified'=>1,'captain_id'=>null,'team_member_ids'=>null]);
                        DB::commit();
                        return response()->json(['status' => 'success', 'message' => 'Job Rescheduled Successfully']);
                    }else{
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => 'The job date is the same. No changes were made.'], 422);
                    }
                }else{
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'The Job has Already been Completed. You Cannot Modify it.'], 422);
                }
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Job Not Found.'], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to  Add Job. ' .$e->getMessage()],500);
        }
    }

    // assign job to sales manager
    public function assignJob(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->merge([
                'team_member_ids' => explode(',', $request->input('team_member_ids')),
            ]);
            $request->validate([
                'job_id' => 'required|exists:jobs,id',
                'captain_id' => 'required|exists:users,id,role_id,4', //user_id
                'team_member_ids.*' => 'required|exists:users,id,role_id,4',
                'job_instructions' => 'nullable|string',
            ]);

            $teamMemberIds = json_encode($request->input('team_member_ids'));

            $job = Job::find($request->job_id);
            if($job){
                if($job->is_completed==0){
                    $job->update(['captain_id'=>$request->captain_id,'team_member_ids'=>$teamMemberIds,'job_instructions'=>$request->job_instructions]);
                    DB::commit();
                    return response()->json(['status' => 'success','message' => 'Job has been Assigned Successfully']);
                }else{
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'The Job has Already been Completed.'],500);
                }
            }else{
                DB::rollBack();
                return response()->json(['status'=>'error', 'message' => 'Job Not Found.'], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Assign Job. ' .$e->getMessage()],500);
        }
    }

    //
    public function moveToComplete($job_id){
        try {
            DB::beginTransaction();

            // Find by ID
            $job = Job::findOrFail($job_id);
            if($job->is_completed==1){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The Job has Already been Completed.'],500);
            }
            
            $job->update(['is_completed'=>1]);
            if($job){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Job Moved to Completed Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Move Complete,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Job Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Move Complete. ' . $e->getMessage(),500]);
        } 
    }
}

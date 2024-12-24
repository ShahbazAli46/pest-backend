<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobRescheduleDetail;
use App\Models\JobService;
use App\Models\Service;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceDetail;
use App\Traits\GeneralTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    use GeneralTrait;
    //
    public function index(Request $request,$id)
    {
        $is_int = filter_var($id, FILTER_VALIDATE_INT);
        $type=$id;
        if ($is_int === false) {
            if ($type == 'pending' || $type == 'completed') {
                $is_completed = $type == 'pending' ? 0 : 1;
                $jobs = Job::with(['user.client.referencable','captain','report:id,job_id','clientAddress','rescheduleDates'])->where('is_completed', $is_completed);
                
                // Apply client_id filter if present
                if ($request->has('user_id')) {
                    $jobs->where('user_id', $request->input('user_id'));
                }
                
                // Check if date filters are present
                if ($request->has('start_date') && $request->has('end_date')) {
                    $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                    $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
                    $jobs = $jobs->whereBetween('job_date', [$startDate, $endDate]);
                }
                $jobs = $jobs->orderBy('id', 'DESC')->get();
            } else {
                $jobs = Job::with(['user.client.referencable','captain','report:id,job_id','clientAddress','rescheduleDates']);

                // Apply client_id filter if present
                if ($request->has('user_id')) {
                    $jobs->where('user_id', $request->input('user_id'));
                }

                // Check if date filters are present
                if ($request->has('start_date') && $request->has('end_date')) {
                    $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                    $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
                    $jobs = $jobs->whereBetween('job_date', [$startDate, $endDate]);
                }
                $jobs = $jobs->orderBy('id', 'DESC')->get();
            }

            if($request->has('start_date') && $request->has('end_date')){
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $jobs]);
            }else{
                return response()->json(['type'=>$type,'data' => $jobs]);
            }
        }else{
            $job = Job::with(['user.client.referencable', 'termAndCondition', 'jobServices.service','rescheduleDates','clientAddress','captain'])->find($id);
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
        $res=$this->createJob($request);
        if($res->original['status']=='success'){

            //create invoices
            $data=$res->original['data'];
            $job_services=JobService::where('job_id',$data['job_id'])->get();
            $this->generateServiceInvoice($data['job_id'],Job::class,$request->user_id,$res->original['data']['grand_total'],now(),$job_services);
           
            // $invoice=ServiceInvoice::create([
            //     'invoiceable_id'=>$data['job_id'],
            //     'invoiceable_type'=>Job::class,
            //     'user_id'=>$request->user_id,
            //     'issued_date'=>now(),
            //     'total_amt'=>$res->original['data']['grand_total'],
            //     'paid_amt'=>0.00,
            // ]);
            // if($invoice){
            //     $job_services=JobService::where('job_id',$data['job_id'])->get();
            //     foreach($job_services as $service){
            //         ServiceInvoiceDetail::create([
            //             'service_invoice_id'=>$invoice->id,
            //             'itemable_id'=>$service->service_id,
            //             'itemable_type'=>Service::class,
            //             'job_type'=>'one_time',
            //             'rate'=>$service->rate,
            //             'sub_total'=>$service->sub_total
            //         ]);
            //     }
            // }
        }
        return $res;
    }

    public function rescheduleJob(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([     
                'job_id' => 'required|exists:jobs,id',
                'job_date' => 'required|date_format:Y-m-d H:i:s',
                'reason' => 'nullable|max:1000',
            ]);

            $job=Job::find($request->job_id);
            if($job){
                if($job->is_completed==0){
                    if($job->job_date!=$request->job_date){
                        $job->update(['job_date'=>$request->job_date,'is_modified'=>1,'captain_id'=>null,'team_member_ids'=>null,'assigned_at'=>null]);
                        JobRescheduleDetail::create(['job_id'=>$job->id,'job_date'=>$request->job_date,'reason'=>$request->reason]);
                        DB::commit();
                        return response()->json(['status' => 'success', 'message' => 'Job Rescheduled Successfully']);
                    }else{
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => 'The job date is the same. No changes were made.'], 422);
                    }
                }else if($job->is_completed==1){
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'The Job has Already been Completed. You Cannot Modify it.'], 422);
                }else{
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'The Job has Already been Started. You Cannot Modify it.'], 422);
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
                    $job->update(['captain_id'=>$request->captain_id,'team_member_ids'=>$teamMemberIds,'job_instructions'=>$request->job_instructions,'assigned_at'=>now()]);
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

    public function startJob($job_id){
        try {
            DB::beginTransaction();

            // Find by ID
            $job = Job::findOrFail($job_id);
            if($job->is_completed==1){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The Job has Already been Completed.'],500);
            }else if($job->is_completed==2){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The Job has Already been Started.'],500);
            }
            
            $job->update(['is_completed'=>2,'job_start_time'=>now()]);
            if($job){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Job Moved to Started Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Move Start,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Job Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Move Start. ' . $e->getMessage(),500]);
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
            }else if($job->is_completed==0){
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Please Start This Job Before Proceeding.'], 500);
            }
            
            $job->update(['is_completed'=>1,'job_end_time'=>now()]);
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

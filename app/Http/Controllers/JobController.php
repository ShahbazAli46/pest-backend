<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;

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
                ->orderBy('id', 'DESC')->get()
                ->map(function ($job) {
                    $job->treatment_methods = $job->getTreatmentMethods(); // Call your method to get treatment methods
                    return $job; 
                });
            }else {
                $jobs = Job::with(['user.client.referencable'])
                ->orderBy('id', 'DESC')->get()
                ->map(function ($job) {
                    $job->treatment_methods = $job->getTreatmentMethods(); // Call your method to get treatment methods
                    return $job; 
                });
            }
            return response()->json(['type'=>$type,'data' => $jobs]);
        }else{
            $job = Job::with(['user.client.referencable', 'termAndCondition', 'jobServices.service'])->find($id);
            if ($job) {
                $job->treatment_methods = $job->getTreatmentMethods(); // Call your method to get treatment methods
            }
            return response()->json(['data' => $job]);
        }           
    }

    public function store(Request $request){
        $this->createJob($request);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobService;
use App\Models\JobServiceReport;
use App\Models\JobServiceReportArea;
use App\Models\JobServiceReportProduct;
use App\Models\Product;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobServiceReportController extends Controller
{
    //
    public function index($id)
    {
        if($id == 'all'){
            $job_service_reports=JobServiceReport::orderBy('id', 'DESC')->get();
            return response()->json(['data' => $job_service_reports]);
        }else{
            $job_service_report=JobServiceReport::with(['areas','usedProducts'])->find($id);
            if ($job_service_report) {
                $job_service_report->pest_found_services = $job_service_report->getPestFoundServices();
                $job_service_report->treatment_methods= $job_service_report->getTreatmentMethods(); 
            }
            return response()->json(['data' => $job_service_report]);
        }           
    }

    //
    public function store(Request $request){
        try {
            // $request->merge([
            //     'tm_ids' => explode(',', $request->input('tm_ids')),
            //     'pest_found_ids' => explode(',', $request->input('pest_found_ids')),
            // ]);
            DB::beginTransaction();
            $request->validate([     
                'job_id' => 'required|exists:jobs,id',
                'type_of_visit' => 'nullable|string|max:255',
                'recommendations_and_remarks' => 'nullable|string|max:1000',
                'tm_ids' => 'required|array',
                'tm_ids.*' => 'integer|exists:treatment_methods,id', 
                'pest_found_ids' => 'required|array',
                'pest_found_ids.*' => 'integer|exists:services,id', 

                'addresses' => 'nullable|array',
                'addresses.*.inspected_areas' => 'nullable|string|max:255',
                'addresses.*.manifested_areas' => 'nullable|string|max:255',
                'addresses.*.report_and_follow_up_detail' => 'nullable|string|max:255',
                'addresses.*.infestation_level' => 'nullable|string|max:255',

                'used_products' => 'required|array',
                'used_products.*.product_id' => 'required|integer|exists:products,id',
                'used_products.*.dose' => 'required|numeric|min:0',
                'used_products.*.qty' => 'required|integer|min:1',
                'used_products.*.price' => 'required|numeric|min:0',
                'used_products.*.is_extra' => 'boolean',
            ]);

            // Extract IDs
            $tmIds = $request->input('tm_ids');
            $pestFoundIds = $request->input('pest_found_ids');

            $job=Job::find($request->job_id);
            if($job && !$job->report){
                $requestData = $request->all(); 
                $requestData['tm_ids'] = json_encode($tmIds);
                $requestData['pest_found_ids'] = json_encode($pestFoundIds);

                // Create the job service report
                $job_report = JobServiceReport::create($requestData);
                if($job_report){
                    $inv_products=[];
                    $total_extra=0;
                    // Insert addresses into JobServiceReportArea
                    foreach ($request->input('addresses') as $address) {
                        JobServiceReportArea::create([
                            'job_id' => $job->id,
                            'job_service_report_id' => $job_report->id,
                            'inspected_areas' => $address['inspected_areas'],
                            'manifested_areas' => $address['manifested_areas'],
                            'report_and_follow_up_detail' => $address['report_and_follow_up_detail'],
                            'infestation_level' => $address['infestation_level'],
                        ]);
                    }

                    // Insert used products into JobServiceReportProduct)
                    foreach ($request->input('used_products', []) as $product) {
                        JobServiceReportProduct::create([
                            'job_id' => $job->id,
                            'job_service_report_id' => $job_report->id,
                            'product_id' => $product['product_id'],
                            'dose' => $product['dose'],
                            'qty' => $product['qty'],
                            'total' => $product['dose'] * $product['qty'], 
                            'price' => $product['price'],
                            'is_extra' => $product['is_extra'] ?? 0,
                        ]);

                        if($product['is_extra']==1){
                            array_push($inv_products,$product);
                            $total_extra+=$product['price'];
                        }
                    }

                    //create invoices
                    $invoice=ServiceInvoice::create([
                        'invoiceable_id'=>$job->id,
                        'invoiceable_type'=>Job::class,
                        'user_id'=>$job->user_id,
                        'issued_date'=>now(),
                        'total_amt'=>$total_extra,
                        'paid_amt'=>0.00,
                    ]);
                    if($invoice){
                        foreach($inv_products as $product){
                            ServiceInvoiceDetail::create([
                                'service_invoice_id'=>$invoice->id,
                                'itemable_id'=>$product['product_id'],
                                'itemable_type'=>Product::class,
                                'job_type'=>'one_time',
                                'rate'=>$product['price'],
                                'sub_total'=>$product['price']
                            ]);
                        }
                    }
                    

                    DB::commit();
                    return response()->json(['status' => 'success','message' => 'Job Service Report Added Successfully','data'=>$job_report]);
                }else{
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'Failed to Add Job Service Report,Please Try Again Later.'],500);
                }
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'The Job Service Report has already been submitted. You cannot modify it.'], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to  Add Service Report. ' .$e->getMessage()],500);
        }
    }

}

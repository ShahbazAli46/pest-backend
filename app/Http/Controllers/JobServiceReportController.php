<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobService;
use App\Models\JobServiceReport;
use App\Models\JobServiceReportArea;
use App\Models\JobServiceReportProduct;
use App\Models\Product;
use App\Models\Quote;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceDetail;
use App\Models\Stock;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobServiceReportController extends Controller
{
    //
    use GeneralTrait;
    public function index(Request $request,$id)
    {
        if($id == 'all'){
            $job_service_report_query=JobServiceReport::with(['usedProducts.product.latestDeliveryStock', 
            'job' => function ($query) {
                $query->select('id', 'captain_id','user_id');
            },
            'job.captain' => function ($query) {
                $query->select('id', 'name');
            },
            'job.user' => function ($query) {
                $query->select('id','name');
            },
            'job.user.client' => function ($query) {
                $query->select('id','firm_name','user_id');
            }])->orderBy('id', 'DESC');

            // Check if date filters are present
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
                $job_service_report_query->whereBetween('created_at', [$startDate, $endDate]);
            }
            $job_service_reports=$job_service_report_query->get();
            return response()->json(['data' => $job_service_reports]);
        }else{
            $job_service_report=JobServiceReport::with(['areas','usedProducts.product','job.user','job.serviceInvoice',
            'job.quote'=>function ($query){
                $query->select('id','quote_title','branch_id');
            },'job.quote.branch'])->find($id);
            
            if ($job_service_report) {
                $job_service_report->pest_found_services = $job_service_report->getPestFoundServices();
                $job_service_report->treatment_methods = $job_service_report->getTreatmentMethods(); 
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
                // 'for_office_use' => 'nullable|string|max:1000',
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

                'signature_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', 
            ]);
            // Extract IDs
            $tmIds = $request->input('tm_ids');
            $pestFoundIds = $request->input('pest_found_ids');

            $job=Job::find($request->job_id);

            if($job && !$job->report){

                foreach ($request->input('used_products', []) as $product) {
                    // Call the function to check stock
                    $used_qty=$this->calculateUsedProAmt($product['product_id'],$product['dose'],$product['qty']);

                    $quantityCheck = $this->checkUserStock($product['product_id'],$used_qty,$job->captain_id);
                    if ($quantityCheck !== true) {
                        return $quantityCheck;
                    }
                }

                $requestData = $request->all(); 
                $requestData['tm_ids'] = json_encode($tmIds);
                $requestData['pest_found_ids'] = json_encode($pestFoundIds);

                if ($request->hasFile('signature_img')) {
                    $requestData['signature_img']=$this->saveImage($request->signature_img,'signature_imgs');
                }

                // Create the job service report
                $job_report = JobServiceReport::create($requestData);
                if($job_report){
                    $extra_products=[];
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
                        $jobServiceProduct=JobServiceReportProduct::create([
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
                            array_push($extra_products,$product);
                            $total_extra+=$product['price'];
                        }

                        // Add sales manager stock entry 
                        $stock = Stock::where(['product_id'=> $product['product_id'],'person_id'=>$job->captain_id,'person_type'=>'App\Models\User'])->latest()->first();
                        $old_total_qty=$stock?$stock->total_qty:0;
                        $old_remaining_qty=$stock?$stock->remaining_qty:0;
                        
                        // // Call the function to check stock
                        $used_qty=$this->calculateUsedProAmt($product['product_id'],$product['dose'],$product['qty']);
                        Stock::create([
                            'product_id' => $product['product_id'],
                            'total_qty' => $old_total_qty, 
                            'stock_out' => $used_qty,  
                            'remaining_qty' => $old_remaining_qty-$used_qty, 
                            'person_id' => $job->captain_id,
                            'person_type' => 'App\Models\User',   
                            'link_id' => $jobServiceProduct->id,
                            'link_name' => 'use_stock', 
                        ]);
                    }

                    //create invoices
                    $total_price = $job->grand_total + $total_extra;
                    $this->generateServiceInvoice($job->id,$total_price,$extra_products);

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

    public function storeFeedback(Request $request){
        try {
            DB::beginTransaction();
            $request->validate([     
                'job_service_report_id' => 'required|exists:job_service_reports,id',
                'for_office_use' => 'required|string|max:1000',
            ]);

            $jobServiceReport=JobServiceReport::where('id',$request->job_service_report_id)->first();
            if($jobServiceReport){
                $jobServiceReport->for_office_use=$request->for_office_use;
                $jobServiceReport->update();
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Job Service Report Feedback Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Job Service Report Not Found.'],500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to  Add Service Report Feedback. ' .$e->getMessage()],500);
        }
    }

    public function calculateUsedProAmt($product_id,$dose,$qty){
        $total_dose=$dose * $qty;

        $productData = Product::find($product_id);
        $per_item_qty = $productData->per_item_qty;

        $used_qty = $total_dose / $per_item_qty; 
        return round($used_qty, 2);
    }
}

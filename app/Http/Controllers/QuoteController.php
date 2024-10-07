<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Quote;
use App\Models\QuoteService;
use App\Models\QuoteServiceDate;
use App\Models\Service;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceDetail;
use App\Models\Vendor;
use App\Traits\GeneralTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    use GeneralTrait;
    //
    public function index($id){
        $is_int = filter_var($id, FILTER_VALIDATE_INT);
        $type=$id;
        if ($is_int === false) {
            $is_contracted=$type=='contracted'?1:0;
            $quotes = Quote::with(['user.client.referencable','quoteServices.service','quoteServices.quoteServiceDates'])
            ->where('is_contracted',$is_contracted)
            ->orderBy('id', 'DESC')->get()
            ->map(function ($quote) {
                $quote->treatment_methods = $quote->getTreatmentMethods(); // Call your method to get treatment methods
                return $quote; 
            });
            return response()->json(['type'=>$type,'data' => $quotes]);
        }else{
            $quote = Quote::with(['user.client.referencable', 'termAndCondition', 'quoteServices.service','quoteServices.quoteServiceDates'])->find($id);
            if ($quote) {
                $quote->treatment_methods = $quote->getTreatmentMethods(); // Call your method to get treatment methods
            }
            return response()->json(['data' => $quote]);
        }
    }

    //store and udpate
    public function manage(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([     
                'manage_type' =>'required|in:create,update', 
                'user_id' => 'required|exists:users,id,role_id,5',
                'quote_title' => 'required|string|max:255',
                'client_address_id' => 'required|exists:client_addresses,id',
                'subject' => 'nullable|string|max:255',
                'tm_ids' => 'required|array',
                'tm_ids.*' => 'integer|exists:treatment_methods,id', // Assuming team members are in a table
                'description' => 'nullable|string',
                'trn' => 'nullable|max:100',
                'tag' => 'nullable|string|max:100',
                'duration_in_months' => 'required|integer|min:1',
                'is_food_watch_account' => 'boolean',
                'billing_method' => 'required|in:installments,monthly,service,one_time',
                'dis_per' => 'nullable|numeric|min:0',
                'vat_per' => 'nullable|numeric|min:0',
                'term_and_condition_id' => 'required|exists:terms_and_conditions,id',
            
                // Validate the services array
                'services' => 'required|array',
                'services.*.service_id' => 'required|integer|exists:services,id', 
            
                // Validate the details array inside each service
                'services.*.detail' => 'required|array',
                'services.*.detail.*.job_type' => 'required|string|in:one_time,yearly,monthly,daily,weekly,custom',
                'services.*.detail.*.rate' => 'required|numeric|min:1', 
                'services.*.detail.*.dates' => 'required|array', 
                'services.*.detail.*.dates.*' => 'required|date', 
            ]);

            if ($request->input('billing_method') == 'installments') {
                $request->validate([
                    'no_of_installments' => 'required|integer|min:1',
                ]);                
            }
            $manage_typed='Added';
            $manage_type='Add';
            //in update condition
            if ($request->input('manage_type') == 'update') {
                $request->validate([
                    'quote_id' => 'required|exists:quotes,id',
                ]);  
                $quote = Quote::find($request->input('quote_id'));
                // Check if is_contracted is not 0
                if ($quote->is_contracted == 1) {
                    DB::rollBack();
                    return response()->json(['error' => 'The Quote has Already been Contracted and Cannot be Modified.'], 422);
                }
                $manage_typed='Updated';
                $manage_type='Update';
                //delete old data
                $quote->quoteServices()->delete();
                $quote->quoteServiceDates()->delete();
            }
            // Extract service IDs
            $serviceIds = array_column($request->input('services'), 'service_id');
            $requestData = $request->all(); 
            $requestData['service_ids'] = json_encode($serviceIds);
            $requestData['tm_ids'] = json_encode($request->input('tm_ids'));

            // Initialize subtotal
            $sub_total = 0;

            // Loop through services to calculate subtotal
            foreach ($request->input('services') as $service) {
                foreach ($service['detail'] as $detail) {
                    $dateCount = count($detail['dates']); // Count number of dates
                    $rate = $detail['rate']; // Get the rate
                    $subTotal = $dateCount * $rate; // Calculate subtotal for this service detail
                    $sub_total += $subTotal; // Add to total subtotal
                }
            }

            $requestData['sub_total'] = $sub_total;

            // Calculate VAT amount
            $vatPer = $request->input('vat_per', 0); 
            $vatAmount = ($sub_total * $vatPer) / 100;
            $requestData['vat_amt'] = $vatAmount;

            $discountAmount = isset($requestData['dis_per']) ? ($sub_total * $requestData['dis_per']) / 100 : 0;
            $requestData['dis_amt'] = $discountAmount;

            $grandTotal = $sub_total + $vatAmount - $discountAmount;
            $requestData['grand_total'] = $grandTotal;

            // Create the quote
            if ($request->input('manage_type') == 'create') {
                $quote = Quote::create($requestData);
            }else{
                $quote->update($requestData);
            }
            
            // Insert into quote_services table
            foreach ($request->input('services') as $service) {
                foreach ($service['detail'] as $detail) {
                    $dateCount = count($detail['dates']);
                    $rate = $detail['rate']; // Get the rate
                    $subTotal = $dateCount * $rate; 

                    $quoteService = QuoteService::create([
                        'quote_id' => $quote->id,
                        'service_id' => $service['service_id'],
                        'no_of_services' => $dateCount,
                        'job_type' => $detail['job_type'],
                        'rate' => $rate,
                        'sub_total' => $subTotal, 
                    ]);

                    // Insert dates into quote_service_dates table
                    foreach ($detail['dates'] as $date) {
                        QuoteServiceDate::create([
                            'quote_id' => $quote->id,
                            'quote_service_id' => $quoteService->id,
                            'service_id' => $service['service_id'],
                            'service_date' => $date,
                        ]);
                    }
                }
            }

            if($quote){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Quote '.$manage_typed.' Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to  '.$manage_type.' Quote,Please Try Again Later.'],500);
            }
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to  '.$manage_type.' Quote. ' .$e->getMessage()],500);
        }
    }

    //
    public function moveToContract($quote_id){
        try {
            DB::beginTransaction();
             // Find by ID
            $quote = Quote::findOrFail($quote_id);
            if($quote->is_contracted==1){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The Quote has Already been Contracted.'],500);
            }

            // Get the current date
            $now = \Carbon\Carbon::now();
            $end_date = $now->addMonths($quote->duration_in_months);

            $quote->update(['is_contracted'=>1,'contract_start_date'=>now(),'contract_end_date'=>$end_date]);
            if($quote){
                //create jobs
                $uniqueServiceDates = $quote->quoteServiceDates()->select('service_date')->distinct()->get();
                $requestData = $quote->toArray(); 
                $requestData['quote_id'] = $quote->id; 
                $requestData['job_title'] = $quote->quote_title;
                $requestData['priority'] = 'high';
                $requestData['tm_ids'] = json_decode($quote->tm_ids);
                
                foreach ($uniqueServiceDates as $serviceDate) {
                    // Fetch service dates for this particular date
                    $serviceDates = $quote->quoteServiceDates()->where('service_date',$serviceDate->service_date)->get();
                    $service_ids = [];
                    $service_rates = [];
                    foreach ($serviceDates as $s_date) {
                        $relatedQuoteService = $s_date->quoteService; // This gives the related model, not the relationship
                        if ($relatedQuoteService) {
                            array_push($service_ids, $relatedQuoteService->service_id);
                            array_push($service_rates, $relatedQuoteService->rate);
                        }
                    }
                    $requestData['service_ids'] = $service_ids;
                    $requestData['service_rates'] = $service_rates;

                    $requestData['job_date'] = $serviceDate->service_date;
                
                    $request = new Request();
                    $request->merge($requestData);

                    $job = $this->createJob($request);
                    if($job->original['status']=='error'){
                        return response()->json(['status' => 'error','message' => 'Failed to Create Job,Please Try Again Later.'],500);
                    }
                }
                
                //create invoices
                $installments=0;
                if($quote->billing_method == 'installments'){
                    $installments=$quote->duration_in_months/$quote->no_of_installments;
                }else if($quote->billing_method == 'service'){
                    $installments = $quote->jobs()->count();
                }else if($quote->billing_method == 'monthly'){
                    $installments=$quote->duration_in_months;
                }else{
                    $installments=1;
                }
                $inst_total=$quote->grand_total;
                for($i=1; $i<=$installments; $i++){
                    $invoice=ServiceInvoice::create([
                        'invoiceable_id'=>$quote->id,
                        'invoiceable_type'=>Quote::class,
                        'user_id'=>$quote->user_id,
                        'issued_date'=>now(),
                        'total_amt'=>$inst_total/$installments,
                        'paid_amt'=>0.00,
                    ]);
                    if($invoice){
                        $quot_services=$quote->quoteServices;
                        foreach($quot_services as $service){
                            ServiceInvoiceDetail::create([
                                'service_invoice_id'=>$invoice->id,
                                'itemable_id'=>$service->service_id,
                                'itemable_type'=>Service::class,
                                'job_type'=>$service->job_type,
                                'rate'=>$service->rate,
                                'sub_total'=>$service->sub_total
                            ]);
                        }
                    }
                }

                DB::commit();
                return response()->json(['status' => 'success','message' => 'Quote Moved to Contract Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Move Contract,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Quote Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Move Contract. ' . $e->getMessage(),500]);
        } 
    }
}

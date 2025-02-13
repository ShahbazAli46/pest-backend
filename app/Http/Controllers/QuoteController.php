<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Job;
use App\Models\Ledger;
use App\Models\Quote;
use App\Models\QuoteService;
use App\Models\QuoteServiceDate;
use App\Models\Service;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceDetail;
use App\Models\User;
use App\Models\Vendor;
use App\Traits\GeneralTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QuoteController extends Controller
{
    use GeneralTrait;
    //
    public function index(Request $request,$id){
        $is_int = filter_var($id, FILTER_VALIDATE_INT);
        $type=$id;
        if ($is_int === false) {
            // $is_contracted=$type=='contracted'?1:0;
            $quotes = Quote::with(['user.client.referencable', 'quoteServices.service', 'quoteServices.quoteServiceDates']);
            
            if($type=='contracted'){
                $quotes->where('is_contracted', 1);
            }

            // Check if date filters are present
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
                $quotes = $quotes->whereBetween('created_at', [$startDate, $endDate]);
            }

            $quotes = $quotes->orderBy('id', 'DESC')->get()->map(function ($quote) {
                $quote->treatment_methods = $quote->getTreatmentMethods(); // Call your method to get treatment methods
                return $quote; 
            });

            // $quotes = Quote::with(['user.client.referencable','quoteServices.service','quoteServices.quoteServiceDates'])
            // ->where('is_contracted',$is_contracted)
            // ->orderBy('id', 'DESC')->get()
            // ->map(function ($quote) {
            //     $quote->treatment_methods = $quote->getTreatmentMethods(); // Call your method to get treatment methods
            //     return $quote; 
            // });
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
                // 'trn' => 'nullable|max:100',
                'tag' => 'nullable|string|max:100',
                'duration_in_months' => 'required|integer|min:1',
                // 'is_food_watch_account' => 'boolean',
                'billing_method' => 'required|in:service',//installments,monthly,one_time
                // 'dis_per' => 'nullable|numeric|min:0',
                'dis_amt' => 'nullable|numeric|min:0',
                'vat_per' => 'nullable|numeric|min:0',
                'term_and_condition_id' => 'required|exists:terms_and_conditions,id',
            
                // Validate the services array
                'services' => 'required|array',
                'services.*.service_id' => 'required|integer|exists:services,id', 
            
                // Validate the details array inside each service
                'services.*.detail' => 'required|array',
                'services.*.detail.*.job_type' => 'required|string|in:one_time,yearly,monthly,daily,weekly,custom',
                'services.*.detail.*.rate' => 'required|numeric|min:1', 
                // 'services.*.detail.*.dates' => 'required|array', 
                // 'services.*.detail.*.dates.*' => 'required|date', 
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
                }else{
                    if ($quote->contract_cancelled_at!=null) {
                        DB::rollBack();
                        return response()->json(['status' => 'error','message' => 'This quote has been cancelled, so you may not perform any changes on it.'], 422);
                    }
                }

                $manage_typed='Updated';
                $manage_type='Update';
                //delete old data
                $quote->quoteServices()->delete();
                // $quote->quoteServiceDates()->delete();
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
                    // $dateCount = count($detail['dates']); // Count number of dates
                    $dateCount=$detail['no_of_jobs'];
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

            // $discountAmount = isset($requestData['dis_per']) ? ($sub_total * $requestData['dis_per']) / 100 : 0;
            $discountAmount = isset($requestData['dis_amt']) ? $requestData['dis_amt'] : 0;
            if ($subTotal > 0) {
                $discountPer = ($discountAmount / $subTotal) * 100;
            } else {
                $discountPer = 0; 
            }
            $requestData['dis_per'] = $discountPer;

            $grandTotal = $sub_total + $vatAmount - $discountAmount;
            $requestData['grand_total'] = $grandTotal;
            
            // Create the quote
            if ($request->input('manage_type') == 'create') {
                $client = Client::where('user_id', $request->user_id)->first();
                $requestData['client_id'] = $client?$client->id:0;
                $quote = Quote::create($requestData);
            }else{
                $quote->update($requestData);
            }
            
            // Insert into quote_services table
            foreach ($request->input('services') as $service) {
                foreach ($service['detail'] as $detail) {
                    // $dateCount = count($detail['dates']);
                    $dateCount=$detail['no_of_jobs'];
                    
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
                    // foreach ($detail['dates'] as $date) {
                    //     QuoteServiceDate::create([
                    //         'quote_id' => $quote->id,
                    //         'quote_service_id' => $quoteService->id,
                    //         'service_id' => $service['service_id'],
                    //         'service_date' => $date,
                    //     ]);
                    // }
                }
            }

            if($quote){
                DB::commit();
                
                // Attempt to send the quote mail
                try {
                    Mail::to($quote->user->email)->send(new \App\Mail\QuoteMail($quote));
                } catch (\Exception $e) {
                    // Log the email error, but do not rollback
                    Log::error('Failed to send quote email: ' . $e->getMessage());
                }

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
    public function moveToContract(Request $request,$quote_id){
        try {
            DB::beginTransaction();
            $request->validate([     
                'trn' => 'nullable|max:100',
                'license_no' => 'nullable|string|max:100',
                'is_food_watch_account' => 'nullable|boolean',

                // Validate the quote_services array
                'quote_services' => 'required|array',
                'quote_services.*.quote_service_id' => 'required|integer|exists:quote_services,id', 
            
                'quote_services.*.dates' => 'required|array', 
                'quote_services.*.dates.*' => 'required|date', 
            ]);


             // Find by ID
            $quote = Quote::findOrFail($quote_id);
            if($quote->is_contracted==1){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The Quote has Already been Contracted.'],500);
            }

            if ($quote->contact_cancelled_at!=null) {
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'This quote has been cancelled, so you may not perform any changes on it.'], 422);
            }

            // Insert into quote_services dates table
            foreach ($request->input('quote_services') as $quote_service) {
                $quote_ser=QuoteService::find($quote_service['quote_service_id']);

                // Insert dates into quote_service_dates table
                foreach ($quote_service['dates'] as $date) {
                    QuoteServiceDate::create([
                        'quote_id' => $quote->id,
                        'quote_service_id' => $quote_ser->id,
                        'service_id' => $quote_ser->service_id,
                        'service_date' => $date,
                    ]);
                }
            }

            // Get the current date
            $now = \Carbon\Carbon::now();
            $end_date = $now->addMonths($quote->duration_in_months);

            $quote->update(['is_contracted'=>1,'contract_start_date'=>now(),'contract_end_date'=>$end_date,'trn'=>$request->trn,'license_no'=>$request->license_no,'is_food_watch_account'=>$request->is_food_watch_account]);

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
                
                /*
                //create invoices
                $installments=0;
                if($quote->billing_method == 'installments'){
                    $installments=$quote->no_of_installments;
                }else if($quote->billing_method == 'service'){
                    $installments = $quote->jobs()->count();
                }else if($quote->billing_method == 'monthly'){
                    $installments=$quote->duration_in_months;
                }else{
                    $installments=1;
                }
                
                $inst_total=$quote->grand_total;
                $installmentDatesArr=$this->generateInstallmentDates($quote->duration_in_months,$installments);
                for($i=1; $i<=$installments; $i++){
                    $this->generateServiceInvoice($quote->id,Quote::class,$quote->user_id,$inst_total/$installments,$installmentDatesArr[$i-1],$quote->quoteServices);
                }

                //link jobs with invoices
                $this->linkJobsToInvoice($quote->id);
                

                // Update the CLIENT ledger
                $user=User::find($quote->user_id);
                $lastClientLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => $quote->user_id])->latest()->first();
                $oldCliCashBalance = $lastClientLedger ? $lastClientLedger->cash_balance : 0;
                $newCliCashBalance = $oldCliCashBalance + $quote->grand_total;
                $cli_ledger=Ledger::create([
                    'bank_id' => null, 
                    'description' => 'Quote Payment for client ' . $user->name,
                    'dr_amt' => $quote->grand_total,
                    'payment_type' => 'none',
                    'entry_type' => 'dr',  
                    'cash_balance' => $newCliCashBalance,
                    'person_id' => $quote->user_id,
                    'person_type' => 'App\Models\User',
                ]);
                */
                
                DB::commit();
                // Attempt to send the quote mail
                try {
                    Mail::to($quote->user->email)->send(new \App\Mail\QuoteMail($quote));
                } catch (\Exception $e) {
                    // Log the email error, but do not rollback
                    Log::error('Failed to send quote email: ' . $e->getMessage());
                }

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

    public function moveToCancel(Request $request,$id){
        try {
            DB::beginTransaction();
            
            $request->validate([    
                'contract_cancel_reason' => 'nullable|max:255',
            ]);

            // Find by ID
            $quote = Quote::findOrFail($id);
            if($quote->is_contracted==1){
                $msg_type='Contract';
                if($quote->contract_cancelled_at!=null){
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'The Contract has Already been Cancelled.'],500);
                }
                /*
                $unpaidTotal = ServiceInvoice::where('status', 'unpaid')->where('invoiceable_type', Quote::class)->where('invoiceable_id', $quote->id)
                ->select(DB::raw('SUM(total_amt - paid_amt) as unpaid_total'))
                ->value('unpaid_total');

                $unpaidTotal = $unpaidTotal ?? 0;
                if($unpaidTotal>0){
                    // Update the CLIENT ledger
                    $user=User::find($quote->user_id);
                    $lastClientLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => $quote->user_id])->latest()->first();
                    $oldCliCashBalance = $lastClientLedger ? $lastClientLedger->cash_balance : 0;
                    $newCliCashBalance = $oldCliCashBalance - $unpaidTotal;
                    $cli_ledger=Ledger::create([
                        'bank_id' => null, 
                        'description' => 'Contract cancellation for client ' . $user->name . ' (Contract ID: ' . $quote->id . ')',
                        'cr_amt' => $unpaidTotal,
                        'payment_type' => 'none',
                        'entry_type' => 'cr',  
                        'cash_balance' => $newCliCashBalance,
                        'person_id' => $quote->user_id,
                        'person_type' => 'App\Models\User',
                    ]);
                }*/
            }else{
                $msg_type='Quote';
                if($quote->contract_cancelled_at!=null){
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'The Quote has Already been Cancelled.'],500);
                }
            }

            $quote->update(['contract_cancelled_at'=>now(),'contract_cancel_reason'=>$request->contract_cancel_reason]);

            DB::commit();
            return response()->json(['status' => 'success','message' => 'The '.$msg_type.' has been Cancelled Successfully']);

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
    
    public function getContractServiceInvoices($id){
        $quote = Quote::with(['user.client.referencable', 'termAndCondition', 'quoteServices.service','quoteServices.quoteServiceDates','invoices.advanceCheques.bank'])->find($id);
        if ($quote) {
            foreach ($quote->invoices as $invoice) {
                $invoice->jobs = $invoice->getJobs();
            }
            $quote->treatment_methods = $quote->getTreatmentMethods(); // Call your method to get treatment methods
        }
        return response()->json(['data' => $quote]);
    }
    
    /*
    public function updateContractDate(Request $request){
        try {
            DB::beginTransaction();
             // Find by ID
            $contract = Quote::findOrFail($request->contract_id);
            if($contract->is_contracted !=1){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The Contrat is not Started Yet.'],500);
            }

           

            // $quote->update(['is_contracted'=>1,'contract_start_date'=>now(),'contract_end_date'=>$end_date]);
            if($contract){
                // $request->quote_services

                //delete all dates after day next
                foreach ($request->quote_services as $quote_service) {
                    $exist_quote_service=$contract->quoteServices()->where('id', $quote_service->quote_service_id)->first();
                    
                    // Ensure we get a single instance of QuoteService
                    $exist_quote_service->quoteServiceDates()->where('service_date', '>', now()->addDay())->delete();

                    foreach($quote_service->quote_service_dates as $date){
                        QuoteServiceDate::create([
                            'quote_id' => $contract->id,
                            'quote_service_id' => $quote_service->quote_service_id,
                            'service_id' => $exist_quote_service->service_id,
                            'service_date' => $date,
                        ]);
                    }
                    
                }


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
                    $installments=$quote->no_of_installments;
                }else if($quote->billing_method == 'service'){
                    $installments = $quote->jobs()->count();
                }else if($quote->billing_method == 'monthly'){
                    $installments=$quote->duration_in_months;
                }else{
                    $installments=1;
                }
                
                $inst_total=$quote->grand_total;
                $installmentDatesArr=$this->generateInstallmentDates($quote->duration_in_months,$installments);
                for($i=1; $i<=$installments; $i++){
                    $this->generateServiceInvoice($quote->id,Quote::class,$quote->user_id,$inst_total/$installments,$installmentDatesArr[$i-1],$quote->quoteServices);

                    // $invoice=ServiceInvoice::create([
                    //     'invoiceable_id'=>$quote->id,
                    //     'invoiceable_type'=>Quote::class,
                    //     'user_id'=>$quote->user_id,
                    //     'issued_date'=>now(),
                    //     'total_amt'=>$inst_total/$installments,
                    //     'paid_amt'=>0.00,
                    // ]);
                    // if($invoice){
                    //     $quot_services=$quote->quoteServices;
                    //     foreach($quot_services as $service){
                    //         ServiceInvoiceDetail::create([
                    //             'service_invoice_id'=>$invoice->id,
                    //             'itemable_id'=>$service->service_id,
                    //             'itemable_type'=>Service::class,
                    //             'job_type'=>$service->job_type,
                    //             'rate'=>$service->rate,
                    //             'sub_total'=>$service->sub_total
                    //         ]);
                    //     }
                    // }
                }

                // Update the CLIENT ledger
                $user=User::find($quote->user_id);
                $lastClientLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => $quote->user_id])->latest()->first();
                $oldCliCashBalance = $lastClientLedger ? $lastClientLedger->cash_balance : 0;
                $newCliCashBalance = $oldCliCashBalance + $quote->grand_total;
                $cli_ledger=Ledger::create([
                    'bank_id' => null, 
                    'description' => 'Quote Payment for client ' . $user->name,
                    'dr_amt' => $quote->grand_total,
                    'payment_type' => 'none',
                    'entry_type' => 'dr',  
                    'cash_balance' => $newCliCashBalance,
                    'person_id' => $quote->user_id,
                    'person_type' => 'App\Models\User',
                ]);

                DB::commit();
                
                // Attempt to send the quote mail
                try {
                    Mail::to($quote->user->email)->send(new \App\Mail\QuoteMail($quote));
                } catch (\Exception $e) {
                    // Log the email error, but do not rollback
                    Log::error('Failed to send quote email: ' . $e->getMessage());
                }

                return response()->json(['status' => 'success','message' => 'Quote Moved to Contract Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Update Contract Dates,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Contract Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Contract Dates. ' . $e->getMessage(),500]);
        } 
    }
    */
}

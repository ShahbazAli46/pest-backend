<?php

namespace App\Traits;

use App\Models\Attachment;
use App\Models\Client;
use App\Models\Job;
use App\Models\JobRescheduleDetail;
use App\Models\JobService;
use App\Models\Ledger;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceDetail;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

trait GeneralTrait
{
    //add user
    public function addUser(Request $request)
    {
        try {
            $registerUserData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users|max:255',
                'role_id' => 'required|exists:roles,id',
                'branch_id' => 'nullable|exists:branches,id', 
            ]);
            $user_password=$this->generateRandomPassword(12);
            $user = User::create([
                'name' => $registerUserData['name'],
                'email' => $registerUserData['email'],
                'role_id' => $request->role_id,
                'password' => Hash::make($user_password),
                'branch_id' => $registerUserData['branch_id']??null,
            ]);

            $user_role=Role::where('id',$user->role_id)->first()->name;
            $message="Dear User This is your Password ".$user_password;

            try {
                Mail::to($registerUserData['email'])->queue(
                    new \App\Mail\WelcomeMail($user->name, $user_role, $user_password)
                );
            } catch (\Exception $mailException) {
                \Log::error('Failed to send welcome email: ' . $mailException->getMessage());
            }

            // if($request->image){
            //     saveImage($request->image,$user->id,'App\Models\User','users','Employee Photo');
            // }
            return ['status' => 'success','message' => 'User Created','data' => $user];
        }catch (\Illuminate\Validation\ValidationException $e) {
            return ['status'=> 'error','message' => $e->validator->errors()->first()];
        } catch (\Exception $e) {
            // Other unexpected errors
            return ['status' => 'error','message' => 'Failed to Add User. ' . $e->getMessage()];
        }
    }

    // Generating Random Password
    function generateRandomPassword($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()-_=+';
        $password = '';
        $characterCount = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[mt_rand(0, $characterCount)];
        }
        return $password;
    }


    function saveAttachments($attachments, $model_id, $model_type, $folder, $description = null)
    {
        foreach ($attachments as $attachment) {
            $name = $attachment->getClientOriginalName();
            $name = strtolower(str_replace(' ', '-', $name));
            $destinationPath = public_path() . '/upload/' . $folder . '/';
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0775, true);
            }
            $fileName = rand(0, 999) . '.' . $attachment->getClientOriginalExtension();
            $attachment->move($destinationPath, $fileName);

            $fileSize = filesize($destinationPath); 
            $file_size=$fileSize?$fileSize:0;

            // Save the attachment record in the database
            $newAttachment = new Attachment;
            $newAttachment->file_name = $name;
            $newAttachment->file_path = 'upload/' .$folder.'/'. $fileName;
            $newAttachment->file_extension = $attachment->getClientOriginalExtension();
            $newAttachment->file_size = $file_size;
            $newAttachment->attachmentable_id = $model_id;
            $newAttachment->attachmentable_type = $model_type;
            $newAttachment->attachment_description = $description;
            $newAttachment->save();
        }
    }


    function saveImage($image,$folder,$oldFilePath = null){
        // Delete the old file if it exists
        if ($oldFilePath && File::exists(public_path($oldFilePath))) {
            File::delete(public_path($oldFilePath));
        }

        // Generate a unique filename
        $extension = $image->getClientOriginalExtension();
        $fileName =  uniqid() . '.' . $extension;
        $destinationPath = public_path('upload/' . $folder . '/');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $image->move($destinationPath, $fileName);
        return 'upload/' .$folder.'/'. $fileName;    
    }

    public function checkUserStock($product_id,$qty,$person_id)
    {
        $stock=Stock::where(['product_id'=> $product_id,'person_id'=>$person_id,'person_type'=>'App\Models\User'])->latest()->first();
        $remainingStock = $stock ? $stock->remaining_qty : 0;
        if ($remainingStock < $qty) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient Stock.'], 400);
        }
        return true;
    }
   
    function createJob(Request $request)
    {  
        try {
            DB::beginTransaction();
            $request->validate([     
                'user_id' => 'required|exists:users,id,role_id,5',
                'quote_id' => 'nullable|exists:quotes,id',
                'job_title' => 'required|string|max:255',
                'job_date' => 'required|date_format:Y-m-d H:i:s',
                'client_address_id' => 'required|exists:client_addresses,id',
                'subject' => 'nullable|string|max:255',
                'tm_ids' => 'required|array',
                'tm_ids.*' => 'integer|exists:treatment_methods,id', // Assuming team members are in a table
                'description' => 'nullable|string',
                'trn' => 'nullable|max:100',
                'tag' => 'nullable|string|max:100',
                'is_food_watch_account' => 'boolean',
                'priority' => 'required|in:,high,medium,low',
                'dis_per' => 'nullable|numeric|min:0',
                'vat_per' => 'nullable|numeric|min:0',
                'term_and_condition_id' => 'required|exists:terms_and_conditions,id',
            
                // Validate the services array
                'service_ids' => 'required|array',
                'service_ids.*' => 'required|exists:services,id', 
            
                // Validate the details array inside each service
                'service_rates' => 'required|array',
                'service_rates.*' => 'required|min:1', 
            ]);

            // Extract service IDs
            $serviceIds = $request->input('service_ids');
            $serviceRates = $request->input('service_rates');
          
            $requestData = $request->all(); 
            $requestData['service_ids'] = json_encode($serviceIds);
            $requestData['tm_ids'] = json_encode($request->input('tm_ids'));

            // Initialize subtotal
            $sub_total = 0;

            // Loop through services to calculate subtotal
            foreach ($serviceIds as $key => $service) {
                $rate = $serviceRates[$key];
                $subTotal = $rate; 
                $sub_total += $subTotal; 
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
            $job = Job::create($requestData);
            // Insert into job_services table
            foreach ($serviceIds as $key => $service) {
                $rate = $serviceRates[$key];
                $subTotal = $rate; 
                $jobService = JobService::create([
                    'job_id' => $job->id,
                    'quote_id' => $request->input('quote_id',null),
                    'service_id' => $service,
                    'rate' => $serviceRates[$key],
                    'sub_total' => $subTotal, 
                ]);                
            }
            JobRescheduleDetail::create(['job_id'=>$job->id,'job_date'=>$requestData['job_date'],'reason'=>'Initial Date']);
            DB::commit();
            return response()->json(['status' => 'success','message' => 'Job Added Successfully','data'=>['job_id'=>$job->id,'grand_total'=>$grandTotal]]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to  Add Job. ' .$e->getMessage()],500);
        }
    }

    function generateServiceInvoice($inv_id,$inv_type,$user_id,$total_amt,$issued_date,$item_details,$item_type='Service')
    {
        $inv_data=$inv_type::find($inv_id);
        $invoice=ServiceInvoice::create([
            'invoiceable_id'=>$inv_id,
            'invoiceable_type'=>$inv_type,
            'user_id'=>$user_id,
            'issued_date'=>$issued_date,
            'total_amt'=>$total_amt,
            'paid_amt'=>0.00,
            'address_id'=> $inv_data->client_address_id
        ]);
        if($invoice){
            if($item_type=='Service'){
                foreach($item_details as $item){
                    ServiceInvoiceDetail::create([
                        'service_invoice_id'=>$invoice->id,
                        'itemable_id'=>$item->service_id,
                        'itemable_type'=>Service::class,
                        'job_type'=>$item->job_type,
                        'rate'=>$item->rate,
                        'sub_total'=>$item->sub_total
                    ]);
                }
            }else{
                //item_details='Product'
                foreach($item_details as $item){
                    ServiceInvoiceDetail::create([
                        'service_invoice_id'=>$invoice->id,
                        'itemable_id'=>$item['product_id'],
                        'itemable_type'=>Product::class,
                        'job_type'=>'one_time',
                        'rate'=>$item['price'],
                        'sub_total'=>$item['price']
                    ]);
                }
            }
            
            if($inv_type!='App\Models\Quote'){
                // Update the CLIENT ledger
                $user=User::find($invoice->user_id);
                $lastClientLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => $invoice->user_id])->latest()->first();
                $oldCliCashBalance = $lastClientLedger ? $lastClientLedger->cash_balance : 0;
                $newCliCashBalance = $oldCliCashBalance + $total_amt;
                $cli_ledger=Ledger::create([
                    'bank_id' => null, 
                    'description' => 'Invoice Payment for client ' . $user->name,
                    'dr_amt' => $total_amt,
                    'payment_type' => 'none',
                    'entry_type' => 'dr',  
                    'cash_balance' => $newCliCashBalance,
                    'person_id' => $invoice->user_id,
                    'person_type' => 'App\Models\User',
                ]);
            }
        }
    }

    function generateInstallmentDates($durationInMonths, $installments)
    {
        // Calculate the interval in months (this can be a decimal)
        $intervalInMonths = $durationInMonths / $installments;
        $issueDates = [];

        // Start from today's date for the first invoice
        $currentDate = Carbon::now();

        // Generate installment dates
        for ($i = 0; $i < $installments; $i++) {
            // Push the current installment date to the array
            $issueDates[] = $currentDate->copy()->toDateString();

            // Move to the next installment date by adding the interval in months (even if it's fractional)
            $currentDate->addMonthsWithNoOverflow(floor($intervalInMonths));

            // Handle any fractional leftover part by adjusting the day manually
            $fractionalPart = $intervalInMonths - floor($intervalInMonths);
            if ($fractionalPart > 0) {
                $currentDate->addDays($fractionalPart * 30); // Approximate days for the fractional month
            }
        }

        return $issueDates;
    }

    function linkJobsToInvoice($quoteId)
    {
        $invoices = ServiceInvoice::where('invoiceable_id', $quoteId)
        ->where('invoiceable_type', Quote::class)
        ->orderBy('issued_date')->get();
        if(count($invoices)>1){
            $jobs = Job::where('quote_id', $quoteId)->orderBy('job_date')->get();
            $remainingJobs = collect($jobs); // Start with all jobs
            $lastAssignedJobs = collect();  // Tracks the last assigned jobs

            // return $jobs;
            foreach ($invoices as $index => $invoice) {
                $jobIdsForInvoice=[];
                if($index==0){
                    for($i=0; $i<=count($invoices); $i++){
                        $firstDate = \Carbon\Carbon::parse($invoices[$index+$i]->issued_date)->endOfDay(); // Use endOfDay to include the entire day
                        $jobs_data = $remainingJobs->filter(function ($job) use ($firstDate) {
                            return $job->job_date <= $firstDate;
                        });                                                                                        
                        if ($jobs_data->isNotEmpty()) {
                            foreach ($jobs_data as $job) {
                                $jobIdsForInvoice[] = $job->id;
                            }
                            break;
                        }
                    }
                }else if($index==count($invoices)-1){
                    for($i=1; $i<=count($invoices); $i++){
                        $firstDate = \Carbon\Carbon::parse($invoices[$index-$i]->issued_date)->endOfDay(); // Use endOfDay to include the entire day
                        $jobs_data = $remainingJobs->filter(function ($job) use ($firstDate) {
                            return $job->job_date > $firstDate;
                        });

                        if ($jobs_data->isNotEmpty()) {
                            foreach ($jobs_data as $job) {
                                $jobIdsForInvoice[] = $job->id;
                            }
                            break;
                        }
                    }
                }else{
                    $firstDate = \Carbon\Carbon::parse($invoices[$index-1]->issued_date)->endOfDay(); // Use endOfDay to include the entire day
                    for ($i = 0; $i < count($invoices); $i++) {
                        if (($index + $i) >= count($invoices)) { 
                            $jobs_data = $remainingJobs->filter(function ($job) use ($firstDate) {
                                return $job->job_date > $firstDate;
                            });
                        }else{
                            $secondDate = \Carbon\Carbon::parse($invoices[$index+$i]->issued_date)->endOfDay(); // Use endOfDay to include the entire day
                            $jobs_data = $remainingJobs->filter(function ($job) use ($firstDate, $secondDate) {
                                return $job->job_date > $firstDate && $job->job_date <= $secondDate;
                            });
                        }

                        if ($jobs_data->isNotEmpty()) {
                            foreach ($jobs_data as $job) {
                                $jobIdsForInvoice[] = $job->id;
                            }
                            break;
                        }
                    }

                    if (empty($jobIdsForInvoice)) {
                        for($i=1; $i<=count($invoices); $i++){
                            $firstDate = \Carbon\Carbon::parse($invoices[$index-$i]->issued_date)->endOfDay(); // Use endOfDay to include the entire day
                            $jobs_data = $remainingJobs->filter(function ($job) use ($firstDate) {
                                return $job->job_date > $firstDate;
                            });
                            if ($jobs_data->isNotEmpty()) {
                                foreach ($jobs_data as $job) {
                                    $jobIdsForInvoice[] = $job->id;
                                }
                                break;
                            }
                        }
                    }
                }
                $invoice->update([
                    'job_ids' => json_encode($jobIdsForInvoice),
                ]);
            }
        }else{
            $job_ids = Job::where('quote_id', $quoteId)->orderBy('job_date')->pluck('id');
            foreach ($invoices as $index => $invoice) {
                $invoices->update(['job_ids' => json_encode($job_ids)]);
            }
        }
    }


    // // Example of a utility method
    // public function formatDate($date)
    // {
    //     return \Carbon\Carbon::parse($date)->format('Y-m-d');
    // }
}

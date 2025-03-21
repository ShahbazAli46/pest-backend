<?php

namespace App\Http\Controllers;

use App\Models\BankInfo;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Employee;
use App\Models\Ledger;
use App\Models\Quote;
use App\Models\User;
use App\Models\Vendor;
use App\Traits\GeneralTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    use GeneralTrait;

    public function index($id=null){
        if($id==null){
            $clients=User::with(['client.referencable','client.addresses'])->where('role_id',5)->orderBy('id', 'DESC')->get();
            foreach ($clients as $client) {
                $client->current_balance = $client->getCurrentCashBalance(User::class); // Pass the person type
                $client->received_amt = $client->getReceivedAmt(User::class); // Pass the person type
            }
            return response()->json(['data' => $clients]);
        }else{
            $client=User::with(['client.referencable','client.addresses','client.bankInfos'])->where('role_id',5)->where('id',$id)->first();
            $client->current_balance = $client->getCurrentCashBalance(User::class);
            return response()->json(['data' => $client]);
        }
    }

    //get all references
    public function getReference(Request $request)
    {
        // Get active employees with role filtering
        $all_active_employees = User::notFired()->whereIn('role_id', ['2', '3', '4', '6' ,'7' ,'8' ,'9'])->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'type' => User::class 
                ];
            });
    
        // Get all vendors
        $all_vendors = Vendor::all()
            ->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'type' => Vendor::class // Add type identifier
                ];
            });

        // Merge the two collections together
        $references = collect($all_active_employees)->merge($all_vendors); 

        // Apply date filtering and quote counting if date range is provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

            // Map through references to add total_quotes
            $references = $references->map(function ($reference) use ($startDate, $endDate) {
                $quoteStats = Quote::whereBetween('created_at', [$startDate, $endDate])
                ->whereHas('client', function ($query) use ($reference) {
                    $query->where('referencable_type', $reference['type'])
                          ->where('referencable_id', $reference['id']);
                })->selectRaw('COUNT(*) as total_quotes, COALESCE(SUM(grand_total), 0) as grand_total_sum')
                ->first();
                $reference['quote_stats'] = $quoteStats;

                $contractStats = Quote::whereBetween('contract_start_date', [$startDate, $endDate])
                ->where('is_contracted',1)
                ->whereHas('client', function ($query) use ($reference) {
                    $query->where('referencable_type', $reference['type'])
                          ->where('referencable_id', $reference['id']);
                })->selectRaw('COUNT(*) as total_contracts, COALESCE(SUM(grand_total), 0) as grand_total_sum')
                ->first();
                $reference['contract_stats'] = $contractStats;

                return $reference;
            });
        }


        return response()->json($references);
    }
    

    public function storeClient(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users|max:255',
                'firm_name' => 'nullable|string|max:255',
                'phone_number' => 'nullable|string|max:50',
                'mobile_number' => 'nullable|string|max:50',
                'industry_name' => 'nullable|string|max:255',
                'referencable_type' => 'required|string|in:App\Models\User,App\Models\Employee,App\Models\Vendor',
                'referencable_id' => 'required|integer',
                'opening_balance' => 'required|numeric|min:0',
            ]);
            $request->merge(['role_id' => 5]);
            $requestData = $request->all(); 

            $user=$this->addUser($request);
            if($user['status']=='error'){
                DB::rollBack();
                return response()->json(['status'=>'error', 'message' => $user['message']], 422);
            }

            $requestData['user_id'] = $user['data']->id;
            $client=Client::create($requestData);
            $user['data']->client=$client;

            if($client){
                // Add supplier ledger entry
                Ledger::create([
                    'bank_id' => null,  // Assuming null if no specific bank is involved
                    'description' => 'Opening balance for client ' . $user['data']->name,
                    'dr_amt' => $request->opening_balance,
                    'payment_type' => 'opening_balance',
                    'entry_type' => 'dr',  // Debit entry for opening balance
                    'cash_balance' => $request->opening_balance,
                    'person_id' => $user['data']->id,
                    'person_type' => User::class,
                ]);

                DB::commit();
                return response()->json(['status' => 'success','message' => 'Client Added Successfully','data'=>$user['data']]);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Client,Please Try Again Later.'],500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()],422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Client. ' .$e->getMessage()],500);
        }
    }


    /* ================= Client Address =============*/ 
    public function storeClientAddress(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'address' => 'required|string|max:255',
                'city' => 'nullable|string|max:100',
                'lat' => 'nullable|numeric|between:-90,90',  // Latitude should be between -90 and 90
                'lang' => 'nullable|numeric|between:-180,180', // Longitude should be between -180 and 180
                'country' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'area' => 'nullable|string|max:255',
            ]);
            $user=User::with(['client'])->where('id',$request->user_id)->where('role_id',5)->first();
    
            // Check if the user has a client record
            if ($user && $user->client) {
                $client = $user->client;
            } else {
                return response()->json(['status' => 'error', 'message' => 'Client Not Found.'], 404);
            }
            
            $request->merge(['client_id' => $client->id]);
            ClientAddress::create($request->all());

            DB::commit();
            return response()->json(['status' => 'success','message' => 'Client Address Added Successfully']);
           
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Client Address,Please Try Again Later.'.$e->getMessage()],500);
        } 
    }
    
    public function updateClientAddress(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'address_id' => 'required|exists:client_addresses,id',
                'address' => 'required|string|max:255',
                'city' => 'nullable|string|max:100',
                'lat' => 'nullable|numeric|between:-90,90',  // Latitude should be between -90 and 90
                'lang' => 'nullable|numeric|between:-180,180', // Longitude should be between -180 and 180
                'country' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'area' => 'nullable|string|max:255',
            ]);

            // Find the bank by ID
            $client_address = ClientAddress::findOrFail($id);
            $client_address->update($validateData);
            DB::commit();
            return response()->json(['status' => 'success','message' => 'Client Address Updated Successfully']);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Client Address Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Client Address. ' . $e->getMessage()],500);
        } 
    }

    /* ================= Client Bank Info =============*/ 
    public function storeClientBankInfo(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'client_id' => 'required|exists:clients,id',
                'bank_name' => 'required|string|max:100',
                'iban' => 'nullable|string|max:100',
                'account_number' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
            ]);
            
            $request->merge(['linkable_id' => $request->client_id, 'linkable_type' => Client::class]);
            BankInfo::create($request->all());

            DB::commit();
            return response()->json(['status' => 'success','message' => 'Client Bank Info Added Successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Client Bank Info,Please Try Again Later.'.$e->getMessage()],500);
        } 
    }
    
    public function updateClientBankInfo(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'client_id' => 'required|exists:clients,id',
                'bank_name' => 'required|string|max:100',
                'iban' => 'nullable|string|max:100',
                'account_number' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
            ]);

            // Find the bank by ID
            $bank_info = BankInfo::findOrFail($id);
            $bank_info->update($validateData);
            DB::commit();
            return response()->json(['status' => 'success','message' => 'Client  Bank Info Updated Successfully']);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Client  Bank Info Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Client  Bank Info. ' . $e->getMessage()],500);
        } 
    }
     
    /* ================= Client Ledger =============*/ 
    public function getClientLedger(Request $request,$id=null){
        if($id==null){
            $client_user_arr = User::where('role_id', 5)->pluck('id')->toArray();
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                $ledgers = Ledger::with(['personable.client.referencable'])->whereIn('person_id',$client_user_arr)->whereBetween('created_at', [$startDate, $endDate])->where(['person_type' => 'App\Models\User'])->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
            }else{
                $ledgers = Ledger::with(['personable.client.referencable'])->whereIn('person_id',$client_user_arr)->where(['person_type' => 'App\Models\User'])->get();
                return response()->json(['data' => $ledgers]);
            }
        }else{
            try {
                if($request->has('start_date') && $request->has('end_date')){
                    $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                    $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                    $ledgers = Ledger::with(['personable.client.referencable'])->whereBetween('created_at', [$startDate, $endDate])->where(['person_type' => 'App\Models\User','person_id' => $id])->get();
                    return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
                }else{
                    $ledgers = Ledger::with(['personable.client.referencable'])->where(['person_type' => 'App\Models\User','person_id' => $id])->get();
                    return response()->json(['data' => $ledgers]);
                }
            } catch (ModelNotFoundException $e) {
                return response()->json(['status'=>'error', 'message' => 'User Not Found.'], 404);
            }
        }
    }

    /* ================= Client Ledger =============*/ 
    public function getClientReceivedAmt(Request $request,$id=null){
        if($id==null){
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

                $clients=User::with(['client'])->where('role_id',5)->orderBy('id', 'DESC')->get()
                ->map(function($client) use ($startDate, $endDate) {
                    $ledgerEntries = Ledger::with(['referenceable'])->where('person_id', $client->id)
                        ->whereBetween('created_at', [$startDate, $endDate])->where('person_type', 'App\Models\User')->get();

                        $crAmtSum = $ledgerEntries->sum('cr_amt'); // Correctly sums all rows
                        if ($crAmtSum > 0) {
                            $client->ledger_cr_amt_sum = $crAmtSum; // Add the sum to the client
                            $client->ledger_entries = $ledgerEntries; // Add the ledger entries to the client
                            return $client; // Return the modified client object
                        }
                        return null;
                    })->filter() ->values();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $clients]);
            }else{
                $clients=User::with(['client'])->where('role_id',5)->orderBy('id', 'DESC')->get()
                ->map(function($client){
                    $ledgerEntries = Ledger::with(['referenceable'])->where('person_id', $client->id)->where('person_type', 'App\Models\User')->get();
                    $crAmtSum = $ledgerEntries->sum('cr_amt'); // Correctly sums all rows

                    // $crAmtSum = Ledger::where('person_id',$client->id)->where(['person_type' => 'App\Models\User'])->sum('cr_amt');
                        if ($crAmtSum > 0) {
                            $client->ledger_cr_amt_sum = $crAmtSum;
                            $client->ledger_entries = $ledgerEntries; // Add the ledger entries to the client
                            return $client;
                        }
                        return null;
                    })->filter() ->values();
                return response()->json(['data' => $clients]);
            }
        }else{
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

                $client=User::with(['client'])->where('role_id',5)->where('id',$id)->first();
                $ledgerEntries = Ledger::with(['referenceable'])->where('person_id', $client->id)->whereBetween('created_at', [$startDate, $endDate])->where('person_type', 'App\Models\User')->get();
                $crAmtSum = $ledgerEntries->sum('cr_amt'); // Correctly sums all rows
                $client->ledger_cr_amt_sum=$crAmtSum;
                $client->ledger_entries = $ledgerEntries;
                // $client->ledger_cr_amt_sum = Ledger::where('person_id',$client->id)->whereBetween('created_at', [$startDate, $endDate])->where(['person_type' => 'App\Models\User'])->sum('cr_amt');
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $client]);
            }else{
                // $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                // $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

                $client=User::with(['client'])->where('role_id',5)->where('id',$id)->first();
                // $client->ledger_cr_amt_sum = Ledger::where('person_id',$client->id)->where(['person_type' => 'App\Models\User'])->sum('cr_amt');
                $ledgerEntries = Ledger::with(['referenceable'])->where('person_id', $client->id)->where('person_type', 'App\Models\User')->get();
                $crAmtSum = $ledgerEntries->sum('cr_amt'); // Correctly sums all rows
                $client->ledger_cr_amt_sum=$crAmtSum;
                $client->ledger_entries = $ledgerEntries; // Add the ledger entries to the client
                return response()->json(['data' => $client]);
            }
        }
    }

    
    /* ================= Client jobs =============*/ 
    public function getClientJobs(Request $request,$id){
        if($request->has('start_date') && $request->has('end_date')){
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
            $client=User::with([
                'client.referencable',
                'clientJobs' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('job_date', [$startDate, $endDate])
                    ->withActiveQuoteOrCompletedJobs();//Contract cancelled condition
                },
                'clientJobs.rescheduleDates',
                'clientJobs.termAndCondition',
                'clientJobs.clientAddress',
                'clientJobs.captain',
                'clientJobs.jobServices.service',
            ])->where('role_id',5)->where('id',$id)->first();

            return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $client]);
        }else{
            $client=User::with([
                'client.referencable',
                'clientJobs' => function ($query) {
                    $query->withActiveQuoteOrCompletedJobs();
                },
                'clientJobs.rescheduleDates',
                'clientJobs.termAndCondition',
                'clientJobs.clientAddress',
                'clientJobs.captain',
                'clientJobs.jobServices.service'
            ])->where('role_id',5)->where('id',$id)->first();
            return response()->json(['data' => $client]);
        }
    }

}

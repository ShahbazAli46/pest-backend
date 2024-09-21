<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Employee;
use App\Models\Ledger;
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
            return response()->json(['data' => $clients]);
        }else{
            $client=User::with(['client.referencable','client.addresses'])->where('role_id',5)->where('id',$id)->first();
            return response()->json(['data' => $client]);
        }
    }

    //get all references
    public function getReference(Request $request)
    {
        // Get active employees with role filtering
        $all_active_employees = User::active()
            ->whereIn('role_id', ['2', '3', '4', '6'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'type' => Employee::class 
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
                'referencable_type' => 'required|string|in:App\Models\Employee,App\Models\Vendor',
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

            if($client){

                // Add supplier ledger entry
                Ledger::create([
                    'bank_id' => null,  // Assuming null if no specific bank is involved
                    'description' => 'Opening balance for client ' . $client->supplier_name,
                    'dr_amt' => $request->opening_balance,
                    'payment_type' => 'opening_balance',
                    'entry_type' => 'dr',  // Debit entry for opening balance
                    'cash_balance' => $request->opening_balance,
                    'person_id' => $client->id,
                    'person_type' => Client::class,
                ]);

                DB::commit();
                return response()->json(['status' => 'success','message' => 'Client Added Successfully']);
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
            ]);
            $user=User::active()->with(['client'])->where('id',$request->user_id)->where('role_id',5)->first();
          
            // Check if the user has a client record
            if (!$user->client->isEmpty()) {
                $client = $user->client->first();
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
            return response()->json(['status'=>'error','message' => 'Failed to Update Client Address. ' . $e->getMessage(),]);
        } 
    }
    
}

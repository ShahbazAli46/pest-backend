<?php

namespace App\Http\Controllers;

use App\Models\BankInfo;
use App\Models\EmployeeCommission;
use App\Models\Ledger;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    //
    public function index($id=null){
        if($id==null){
            $vendors=Vendor::orderBy('id', 'DESC')->get();
            return response()->json(['data' => $vendors]);
        }else{
            $vendor=Vendor::with(['bankInfos'])->where('id',$id)->first();
            return response()->json(['data' => $vendor]);
        }
    }


    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:vendors,email',
                'contact' => 'nullable|string|max:50',
                'firm_name' => 'nullable|string|max:255',
                'mng_name' => 'nullable|string|max:255',
                'mng_contact' => 'nullable|string|max:50',
                'mng_email' => 'nullable|email|max:255',
                'acc_name' => 'nullable|string|max:255',
                'acc_contact' => 'nullable|string|max:50', 
                'acc_email' => 'nullable|email|max:255',
                'percentage' => 'nullable|numeric|min:0|max:100', 
                'tag' => 'nullable|string|max:255',
                'opening_balance' => 'required|numeric|min:0',
                'vat' => 'nullable|numeric|min:0|max:100',
            ]);
            $openingBalance = is_numeric($request->opening_balance)?$request->opening_balance:0.00;
            $validateData['opening_balance']=$openingBalance;
            $vendor=Vendor::create($validateData);
            if($vendor){
                // Add vendor ledger entry
                Ledger::create([
                    'bank_id' => null,  // Assuming null if no specific bank is involved
                    'description' => 'Opening balance for vendor ' . $request->name,
                    'dr_amt' => $openingBalance,
                    'payment_type' => 'opening_balance',
                    'entry_type' => 'dr',  // Debit entry for opening balance
                    'cash_balance' => $openingBalance,
                    'person_id' => $vendor->id,
                    'person_type' => Vendor::class,
                ]);

                // Create commission entry for the current month
                $currentMonth = now()->format('Y-m'); // Get current month (e.g., "2024-10")
                EmployeeCommission::create([
                    'referencable_id' => $vendor->id,
                    'referencable_type' => Vendor::class,
                    'target' =>0,
                    'commission_per' => $vendor->percentage,
                    'month' => $currentMonth,
                    'status' => 'unpaid',
                ]);
                
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Vendor Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Vendor,Please Try Again Later.'],500);
            }
            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status'=>'error','message' => $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Vendor. ' .$e->getMessage()],500);
        }
    }

    /* ================= Vendor Bank Info =============*/ 
    public function storeVendorBankInfo(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'vendor_id' => 'required|exists:vendors,id',
                'bank_name' => 'required|string|max:100',
                'iban' => 'nullable|string|max:100',
                'account_number' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
            ]);
            
            $request->merge(['linkable_id' => $request->vendor_id, 'linkable_type' => Vendor::class]);
            BankInfo::create($request->all());

            DB::commit();
            return response()->json(['status' => 'success','message' => 'Vendor Bank Info Added Successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Vendor Bank Info,Please Try Again Later.'.$e->getMessage()],500);
        } 
    }
    
    public function updateVendorBankInfo(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'vendor_id' => 'required|exists:vendors,id',
                'bank_name' => 'required|string|max:100',
                'iban' => 'nullable|string|max:100',
                'account_number' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
            ]);

            // Find the bank by ID
            $bank_info = BankInfo::findOrFail($id);
            $bank_info->update($validateData);
            DB::commit();
            return response()->json(['status' => 'success','message' => 'Vendor Bank Info Updated Successfully']);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Vendor Bank Info Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Vendor Bank Info. ' . $e->getMessage()],500);
        } 
    }

}

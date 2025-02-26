<?php

namespace App\Http\Controllers;

use App\Models\AdvanceCheque;
use App\Models\Bank;
use App\Models\BankInfo;
use App\Models\Ledger;
use App\Models\Supplier;
use App\Traits\LedgerTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    //
    use LedgerTrait;
    public function index($id=null){
        if($id==null){
            $suppliers = Supplier::orderBy('id', 'DESC')->get();
            $ledgers = Ledger::where('person_type', Supplier::class)->orderBy('id', 'DESC')->get();
            foreach ($suppliers as $supplier) {
                $lastLedger = $ledgers
                    ->where('person_id', $supplier->id)->first();
                $supplier->balance = $lastLedger ? $lastLedger->cash_balance : 0.00; 
            }
            
            return response()->json(['data' => $suppliers]);
        }else{
            $supplier=Supplier::with(['bankInfos'])->where('id',$id)->first();
            $ledger = Ledger::where('person_type', Supplier::class)->orderBy('id', 'DESC')->where('person_id', $supplier->id)->first();
            $supplier->balance = $ledger ? $ledger->cash_balance : 0.00; 
            return response()->json(['data' => $supplier]);
        }
    }

    //
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'supplier_name' => 'required|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'email' => 'required|string|email|max:255|unique:suppliers,email',
                'number' => 'nullable|string|max:50',
                'trn_no' => 'nullable|string|max:50',
                'item_notes' => 'nullable|string|max:500',
                'address' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'zip' => 'nullable|string|max:20',
                'opening_balance' => 'required|numeric|min:0',
                'tag' => 'nullable|string|max:255',
            ]);

            $supplier=Supplier::create($validateData);
            if($supplier){

                // Add supplier ledger entry
                $openingBalance = $validateData['opening_balance'] ?? 0;
                Ledger::create([
                    'bank_id' => null,  // Assuming null if no specific bank is involved
                    'description' => 'Opening balance for supplier ' . $supplier->supplier_name,
                    'dr_amt' => $openingBalance,
                    'payment_type' => 'opening_balance',
                    'entry_type' => 'dr',  // Debit entry for opening balance
                    'cash_balance' => $openingBalance,
                    'person_id' => $supplier->id,
                    'person_type' => 'App\Models\Supplier',
                ]);

                DB::commit();
                return response()->json(['status' => 'success','message' => 'Supplier Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Supplier,Please Try Again Later.'],500);
            }
            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Supplier. ' .$e->getMessage()],500);
        }
    }

    public function addPayment(Request $request)
    {
        try {           
            DB::beginTransaction();
            $request->validate([
                'supplier_id' => ['required', 'exists:suppliers,id'],
                'payment_type' => 'required|in:cash,cheque,online',
                'description' => 'nullable|string',
                'amount' => 'required|numeric|min:0',
                'vat_per' => 'nullable|numeric|min:0|max:100',
            ]);

            if ($request->input('payment_type') == 'cheque') {
                $request->validate([
                    // 'bank_id' => 'required|exists:banks,id',
                    'cheque_no' => 'required|string|max:100',
                    'cheque_date' => 'required|date',
                ]);                
            }else if($request->input('payment_type') == 'online'){
                $request->validate([
                    // 'bank_id' => 'required|exists:banks,id',
                    'transection_id' => 'required|string|max:100',
                ]);
            }

            if ($request->input('payment_type') == 'cheque' || $request->input('payment_type') == 'online') {
                $company_bank=$this->getCompanyBank();
                if(!$company_bank){
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'Company Bank Not Found.'],404);
                }
                $request->bank_id=$company_bank->id;
            }
            
            $requestData = $request->all(); 
            // Calculate VAT amount
            $amount = $request->input('amount');
            $vatPer = $request->input('vat_per', 0); 
            $vatAmount = ($amount * $vatPer) / 100;
            $requestData['vat_amount'] = $vatAmount;
            
            // Calculate total_amount
            $requestData['total_amount'] = $amount + $vatAmount;
            if($request->input('payment_type') == 'online'){
                // Call the function to check balances
                $balanceCheck = $this->checkCompanyBalance($request->input('payment_type'),$requestData['total_amount'],$request->bank_id);
                if ($balanceCheck !== true) {
                    return $balanceCheck;
                }
            }
        

            if($request->input('payment_type') == 'cheque'){
                AdvanceCheque::create([
                    // 'user_id' => $invoice->user_id,
                    'description' => $requestData['description']??null,
                    'bank_id' => $request->bank_id??null,
                    'cheque_no' => $request->input('cheque_no')??null,
                    'cheque_date' => $request->input('cheque_date')??null,
                    
                    'linkable_id'=>$request->supplier_id,
                    'linkable_type'=>Supplier::class,
                    'settlement_amt' => 0.00,
                    'cheque_type' => 'pay',
                    'vat_per' => $vatPer,
                    'vat_amount' => $vatAmount,
                    'cheque_amt_without_vat' => $amount,
                    'cheque_amount' => $requestData['total_amount'],
                    'entry_type' => 'supplier_payment',
                    'entry_row_data' => $requestData,
                ]);

                DB::commit();
                return response()->json(['status' => 'success','message' => 'Added Adv Cheque for Supplier Payment Successfully']);
            }else{
                // Update the supplier ledger
                $lastSupLedger = Ledger::where(['person_type' => 'App\Models\Supplier', 'person_id' => $request->supplier_id])->latest()->first();
                $oldSupCashBalance = $lastSupLedger ? $lastSupLedger->cash_balance : 0;
                $newSupCashBalance = $oldSupCashBalance - $requestData['total_amount'];
                $supplier=Supplier::find($request->supplier_id);
                $sup_ledger=Ledger::create([
                    'bank_id' => null,  // Assuming null if no specific bank is involved
                    'description' => $request->description,
                    'cr_amt' => $requestData['total_amount'],
                    'payment_type' => $request->input('payment_type'),
                    'entry_type' => 'cr',  
                    'cash_balance' => $newSupCashBalance,
                    'person_id' => $request->supplier_id,
                    'person_type' => 'App\Models\Supplier',
                ]);
    
                // Update the company ledger
                $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                $newBankBalance=$oldBankBalance;
                if($request->input('payment_type') !== 'cash'){
                    $newBankBalance = $request->input('payment_type') !== 'cash' ? ($oldBankBalance - $requestData['total_amount']) : $oldBankBalance;
                    $bank=Bank::find($request->bank_id);
                    $bank->update(['balance'=>$bank->balance-$requestData['total_amount']]);
                }
                $newCashBalance = $request->input('payment_type') === 'cash' ? ($oldCashBalance - $requestData['total_amount']) : $oldCashBalance;
                Ledger::create([
                    'bank_id' => $request->input('payment_type') !== 'cash' ? $request->bank_id:null, 
                    'description' => 'Add Payment',
                    'dr_amt' => $requestData['total_amount'],
                    'cr_amt' => 0.00,
                    'payment_type' => $request->input('payment_type'),
                    'cash_amt' => $request->input('payment_type') == 'cash' ? $requestData['total_amount'] : 0.00,
                    'cheque_amt' => $request->input('payment_type') == 'cheque' ? $requestData['total_amount'] : 0.00,
                    'online_amt' => $request->input('payment_type') == 'online' ? $requestData['total_amount'] : 0.00,
                    'bank_balance' => $newBankBalance,
                    'cash_balance' => $newCashBalance,
                    'entry_type' => 'dr',
                    'person_id' => 1, // Admin or Company 
                    'person_type' => 'App\Models\User', 
                    'link_id' => $sup_ledger->id, 
                    'link_name' => 'supplier_ledger',
                    'referenceable_id' =>  $supplier->id,
                    'referenceable_type' => 'App\Models\Supplier',
                    'cheque_no' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null,
                    'cheque_date' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null,
                    'transection_id' => in_array($request->input('payment_type'), ['online', 'pos']) ? $request->input('transection_id') : null,
                ]);
    
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Supplier Payment Added Successfully']);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Payment. ' .$e->getMessage()],500);
        }
    }

    public function getSupplierLedger(Request $request,$id=null){
        if($id==null){
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                $ledgers = Ledger::with(['personable'])->whereBetween('created_at', [$startDate, $endDate])->where(['person_type' => 'App\Models\Supplier'])->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
            }else{
                $ledgers = Ledger::with(['personable'])->where(['person_type' => 'App\Models\Supplier'])->get();
                return response()->json(['data' => $ledgers]);
            }
        }else{
            try {
                if($request->has('start_date') && $request->has('end_date')){
                    $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                    $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                    $ledgers = Ledger::with(['personable'])->whereBetween('created_at', [$startDate, $endDate])->where(['person_type' => 'App\Models\Supplier','person_id' => $id])->get();
                    foreach($ledgers as $ledger){
                        $ledger->delivery_note=$ledger->getDeliveryNote();
                    }
                    return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
                }else{
                    $ledgers = Ledger::with(['personable'])->where(['person_type' => 'App\Models\Supplier','person_id' => $id])->get();
                    foreach($ledgers as $ledger){
                        $ledger->delivery_note=$ledger->getDeliveryNote();
                    }
                    return response()->json(['data' => $ledgers]);
                }
            } catch (ModelNotFoundException $e) {
                return response()->json(['status'=>'error', 'message' => 'Supplier Not Found.'], 404);
            }
        }
    }

    /* ================= Supplier Bank Info =============*/ 
    public function storeSupplierBankInfo(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'bank_name' => 'required|string|max:100',
                'iban' => 'nullable|string|max:100',
                'account_number' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
            ]);
            
            $request->merge(['linkable_id' => $request->supplier_id, 'linkable_type' => Supplier::class]);
            BankInfo::create($request->all());

            DB::commit();
            return response()->json(['status' => 'success','message' => 'Supplier Bank Info Added Successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Supplier Bank Info,Please Try Again Later.'.$e->getMessage()],500);
        } 
    }
    
    public function updateSupplierBankInfo(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'supplier_id' => 'required|exists:suppliers,id',
                'bank_name' => 'required|string|max:100',
                'iban' => 'nullable|string|max:100',
                'account_number' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
            ]);

            // Find the bank by ID
            $bank_info = BankInfo::findOrFail($id);
            $bank_info->update($validateData);
            DB::commit();
            return response()->json(['status' => 'success','message' => 'Supplier Bank Info Updated Successfully']);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Supplier Bank Info Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Supplier Bank Info. ' . $e->getMessage()],500);
        } 
    }

}

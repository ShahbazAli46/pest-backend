<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    //
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $serviceIds = explode(',', $request->input('service_ids'));
            $tmIds = explode(',', $request->input('tm_ids'));

            $request->merge([
                'service_ids' => json_encode($serviceIds),
                'tm_ids' => json_encode($tmIds),
            ]);

            $request->validate([        
                'user_id' => 'required|exists:users,id,role_id,5',
                'quote_title' => 'required|string|max:255',
                'client_address_id' => 'required|exists:client_addresses,id',
                'subject' => 'nullable|string|max:255',
                'service_ids' => 'required|json',
                'tm_ids' => 'required|json',
                'description' => 'nullable|string',
                'trn' => 'nullable|string|max:100',
                'tag' => 'nullable|string|max:100',
                'duration_in_months' => 'required|integer|min:1',
                'is_food_watch_account' => 'boolean',
                'billing_method' => 'required|in:installments,monthly,service,one_time',
                'dis_per' => 'nullable|numeric|min:0',
                'vat_per' => 'nullable|numeric|min:0',
                // 'contract_start_date' => 'nullable|date',
                // 'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
                // 'is_contracted' => 'boolean',
                'term_and_condition_id' => 'required|exists:terms_and_conditions,id',
            ]);

            if ($request->input('billing_method') == 'installments') {
                $request->validate([
                    'no_of_installments' => 'required|integer|min:1',
                ]);                
            }

            $requestData = $request->all(); 
            
            // Calculate VAT amount
            $sub_total = $request->input('sub_total',100);

            $vatPer = $request->input('vat_per', 0); 
            $vatAmount = ($sub_total * $vatPer) / 100;
            $requestData['vat_amt'] = $vatAmount;
            
            $discountAmount = isset($requestData['dis_per']) ? ($sub_total * $requestData['dis_per']) / 100 : 0;
            $requestData['dis_amt'] = $discountAmount;
            $grandTotal = $sub_total + $vatAmount - $discountAmount;

            // Calculate total_amount
            $requestData['sub_total'] = $sub_total;
            $requestData['grand_total'] = $grandTotal;

            $quote=Quote::create($requestData);
            if($quote){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Quote Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Quote,Please Try Again Later.'],500);
            }
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Quote. ' .$e->getMessage()],500);
        }
    }

}

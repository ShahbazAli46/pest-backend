<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseCategoryController extends Controller
{
    //Get
    public function index(Request $request,$id=null){
        if($id==null){
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

                $expense_categories=ExpenseCategory::withSum(['expenses as total_amount' => function ($query) use ($startDate, $endDate) {
                    if ($startDate && $endDate) {
                        $query->whereBetween('expense_date', [$startDate, $endDate]);
                    }
                }], 'total_amount')->orderBy('id', 'DESC')->get()
                ->map(function ($category) {
                    $category->total_amount = $category->total_amount ?? "0";
                    return $category;
                });
            }else{
                
                $expense_categories = ExpenseCategory::withSum('expenses as total_amount', 'total_amount')
                ->orderBy('id', 'DESC')->get()
                ->map(function ($category) {
                    $category->total_amount = $category->total_amount ?? "0";
                    return $category;
                });

            }
            return response()->json(['data' => $expense_categories]);
        }else{
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
            
                $expense_category = ExpenseCategory::with(['expenses' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('expense_date', [$startDate, $endDate]);
                }])->find($id);
            } else {
                $expense_category = ExpenseCategory::with('expenses')->find($id);
            }
            return response()->json(['data' => $expense_category]);
        }
    }
    
    //Store
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'expense_category' => 'required|string|max:100|unique:expense_categories,expense_category',
            ]);
            ExpenseCategory::create($validateData);
            DB::commit();
            return response()->json(['status' => 'success','message' => 'Expense Category Added Successfully']);            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Expense Category. ' .$e->getMessage()],500);
        }
    }

    //Update
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'expense_category' => 'required|string|max:100|unique:expense_categories,expense_category,'.$id,
            ]);

             // Find the bank by ID
            $expense_category = ExpenseCategory::findOrFail($id);
            $expense_category->update($validateData);
            if($expense_category){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Expense Category Updated Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Update Expense Category,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Expense Category Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Expense Category. ' . $e->getMessage(),500]);
        } 
    }
}

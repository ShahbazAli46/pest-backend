<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseCategoryController extends Controller
{
    //Get
    public function index($id=null){
        if($id==null){
            $expense_categories=ExpenseCategory::orderBy('id', 'DESC')->get();
            return response()->json(['data' => $expense_categories]);
        }else{
            $expense_category=ExpenseCategory::find($id);
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

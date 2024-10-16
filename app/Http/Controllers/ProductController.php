<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use GeneralTrait;

    public function index($id = null)
    {
        if ($id == null) {
            $products = Product::with(['attachments', 'stocks' => function ($query) {
                $query->select('id', 'product_id', 'total_qty', 'remaining_qty') // Specify the columns you need for stocks
                    ->whereIn('id', function($subQuery) {
                        $subQuery->select(DB::raw('MAX(id)'))->from('stocks')->groupBy('product_id');
                });
            }])->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $products]);
        } else {
            $product = Product::with(['attachments', 'stocks' => function ($query) use ($id){
                $query->select('id', 'product_id', 'total_qty', 'remaining_qty') // Specify the columns you need for stocks
                    ->whereIn('id', function($subQuery) use ($id) {
                        $subQuery->select(DB::raw('MAX(id)'))->from('stocks')->where('product_id', $id)->groupBy('product_id');
                });
            }])->where('id', $id)->first();

            $assigned_stock_history= Stock::with('person')
            ->where('person_id', '!=', 1)
            ->whereNull("link_name")
            ->where('product_id', $id)->get();

            $product->assigned_stock_history=$assigned_stock_history;
            return response()->json(['data' => $product]);
        }
    }

    //
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'product_name' => 'required|string|max:255',
                'batch_number' => 'nullable|string|max:100',
                'brand_id' => 'required|exists:brands,id', 
                'mfg_date' => 'nullable|date|before_or_equal:today',
                'exp_date' => 'nullable|date|after:mfg_date', 
                'product_type' => 'nullable|in:Liquid,Powder,Gel,Pieces', 
                'unit' => 'nullable|string|max:50',
                'active_ingredients' => 'nullable|string|max:255',
                'others_ingredients' => 'nullable|string|max:255',
                'moccae_approval' => 'nullable|string|max:255',
                'moccae_strat_date' => 'nullable|date', 
                'moccae_exp_date' => 'nullable|date|after:moccae_strat_date', 
                'per_item_qty' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:1000', 
                'product_picture' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048', 
                'attachments' => 'nullable|array', 
                'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120', 
            ]);

            $requestData = $request->all(); 
            // Handle the image upload
            if ($request->hasFile('product_picture')) {
                $requestData['product_picture']=$this->saveImage($request->product_picture,'products/pictures');
            }

            $product=Product::create($requestData);
            if ($product && $request->hasFile('attachments')) {
                $this->saveAttachments($request->file('attachments'), $product->id, Product::class, 'products/attachments', 'Product Detail Document');
            }

            DB::commit();
            return response()->json(['status' => 'success','message' => 'Product Added Successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Product. ' .$e->getMessage()],500);
        }
    }

    public function getProductStok($id=null)
    {
        // id==product_id
        if($id==null){
            $stocks=Stock::with(['product:id,product_name'])->where(['person_id'=>1,'person_type'=>'App\Models\User'])->get();
            return response()->json(['data' => $stocks]);
        }else{
            $stocks=Stock::with(['product:id,product_name'])->where(['person_id'=>1,'person_type'=>'App\Models\User','product_id'=>$id])->get();
            return response()->json(['data' => $stocks]);
        }
      
    }
    
}

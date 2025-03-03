<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNoteDetail;
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
                $query->select('id', 'product_id', 'total_qty', 'remaining_qty','avg_price')->where('person_id', 1)
                    ->whereIn('id', function ($subQuery) {
                        $subQuery->select(DB::raw('MAX(id)'))->from('stocks')->where('person_id', 1)
                        ->whereIn('link_name', ['delivery_note_detail','assign_stock','sale_order_detail','opening_stock','add_stock'])
                        ->groupBy('product_id');
                    });
            }])->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $products]);
        } else {
            $product = Product::with(['attachments','stocks' => function ($query) use ($id) {
                $query->select('id', 'product_id', 'total_qty', 'remaining_qty','avg_price')
                    ->where('product_id', $id)
                    ->whereIn('link_name', ['delivery_note_detail','assign_stock','sale_order_detail','opening_stock','add_stock'])
                    ->where('person_id', 1)->orderBy('id', 'DESC')->limit(1);
            }])->where('id', $id)->first();
            
            if($product){
                $product->assigned_stock_history= Stock::with('person')->where('person_id', '!=', 1)->whereNull("link_name")->where('product_id', $id)->get();
                $product->delivery_note_history=DeliveryNoteDetail::with('deliveryNote.supplier')->where('product_id',$id)->get();
            }
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
                'product_category' => 'nullable|string|max:100',
                'opening_stock_qty' =>  'required|numeric|min:0',
                'opening_stock_price' =>  'required|numeric|min:0',
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

            // Add stock entry
            $stock_query = Stock::where(['product_id'=> $product->id,'person_id'=>1,'person_type'=>'App\Models\User']);
            $stock=$stock_query->latest()->first();

            $existingPrices = $stock_query->where('stock_in','>',0.00)->pluck('price')->toArray();
            $allPrices = array_merge($existingPrices, [$request->opening_stock_price]);
            $avg_price = count($allPrices) > 0 ? array_sum($allPrices) / count($allPrices) : 0;

            $old_total_qty=$stock?$stock->total_qty:0;
            $old_remaining_qty=$stock?$stock->remaining_qty:0;
            Stock::create([
                'product_id' => $product->id,
                'total_qty' => $old_total_qty+$request->opening_stock_qty, 
                'stock_in' => $request->opening_stock_qty,
                'price' => $request->opening_stock_price,
                'avg_price' => $avg_price,
                'remaining_qty' => $old_remaining_qty+$request->opening_stock_qty, 
                'person_id' => 1,
                'person_type' => 'App\Models\User',  
                'link_name' => 'opening_stock', 
            ]);

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
    
    //add new stock
    public function addStock(Request $request){
        try {
            DB::beginTransaction();
            $request->validate([
                'product_id' => 'required|exists:products,id', 
                'add_stock_qty' =>  'required|numeric|min:1',
                'add_stock_price' =>  'required|numeric|min:1',
            ]);

            // Add stock entry
            $stock_query = Stock::where(['product_id'=> $request->product_id,'person_id'=>1,'person_type'=>'App\Models\User']);
            $stock=$stock_query->latest()->first();
            $existingPrices = $stock_query->where('stock_in','>',0.00)->pluck('price')->toArray();
            $allPrices = array_merge($existingPrices, [$request->add_stock_price]);
            $avg_price = count($allPrices) > 0 ? array_sum($allPrices) / count($allPrices) : 0;

            $old_total_qty=$stock?$stock->total_qty:0;
            $old_remaining_qty=$stock?$stock->remaining_qty:0;
            Stock::create([
                'product_id' => $request->product_id,
                'total_qty' => $old_total_qty+$request->add_stock_qty, 
                'stock_in' => $request->add_stock_qty,
                'price' => $request->add_stock_price,
                'avg_price' => $avg_price,
                'remaining_qty' => $old_remaining_qty+$request->add_stock_qty, 
                'person_id' => 1,
                'person_type' => 'App\Models\User',  
                'link_name' => 'add_stock', 
            ]);

            DB::commit();
            return response()->json(['status' => 'success','message' => 'Stock Added Successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Stock.' .$e->getMessage()],500);
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

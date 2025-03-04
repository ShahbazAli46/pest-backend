<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientsResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\RenewableItemModel;
use Illuminate\Support\Facades\Response;

class RenewableItemController extends Controller
{
    function index($searchStr=null)
    {
        $perPage = request('per_page', 10);
        $ispager = true;
        if($searchStr){
            if(strtolower($searchStr)  == 'all'){
                $ispager = false;
                $data = RenewableItemModel::orderBy('id', 'desc')->get();
            }else{
                $data = RenewableItemModel::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchStr) . '%'])
                ->orderBy('id', 'desc')
                ->paginate($perPage);
            }
        }else{
            $data = RenewableItemModel::orderBy('id', 'desc')->paginate($perPage);
        }
        $this->data = $data;
        if($this->data){
            $this->data = ['data' => $this->data];
            if($ispager){
                $this->data['pagination'] = [
                        'total' => $data->total(),
                        'per_page' => $data->perPage(),
                        'current_page' => $data->currentPage(),
                        'last_page' => $data->lastPage(),
                        'from' => $data->firstItem(),
                        'to' => $data->lastItem(),
                ];
            } 
            
            $this->responsee(true);
        }
        else{
            $this->responsee(false, $this->d_err);
        }
        return $this->json_response($this->resp, $this->httpCode);
    }
    
    function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'nullable|string|max:255',
            'type'             => 'nullable|string|max:100',
            'start_date'       => 'nullable|date',
            'last_renew_date'  => 'nullable|date',
            'expiry_date'      => 'nullable|date|after_or_equal:start_date',
            'notified'         => 'boolean',
            'remarks'          => 'nullable|string',
        ]);

        if ($validator->fails())
            $this->responsee(false, $validator->errors()->all());
        else{
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('renewable_items', 'public');
                $request->merge(['file_path' => $filePath]);
            }
            $this->data = RenewableItemModel::create($request->all());
            if($this->data){
                    $this->s_msg = 'Client has been added successfully';
                    $this->responsee(true);
            }
            else{
                $this->responsee(false);
            }
        }
        return $this->json_response($this->resp, $this->httpCode);
    }
    public function edit($id)
    {
        if($id){
            $this->data = RenewableItemModel::find($id);
            if($this->data)
                $this->responsee(true);
            else
                $this->responsee(false, $this->d_err);
        }else
            $this->responsee(false, $this->id_err);
        return $this->json_response($this->resp, $this->httpCode);
    }

    public function update(Request $request, $id)
    {
        // $input = $request->all();
        $validator = Validator::make($request->all(), [
            'name'             => 'nullable|string|max:255',
            'type'             => 'nullable|string|max:100',
            'start_date'       => 'nullable|date',
            'last_renew_date'  => 'nullable|date',
            'expiry_date'      => 'nullable|date|after_or_equal:start_date',
            'notified'         => 'boolean',
            'remarks'          => 'nullable|string',
        ]);

        if ($validator->fails())
            $this->responsee(false, $validator->errors()->all());
        else{
            $this->data = RenewableItemModel::find($id);
            if($this->data){
                if ($request->hasFile('file')) {
                    $filePath = $request->file('file')->store('renewable_items', 'public');
                    $request->merge(['file_path' => $filePath]);
                }
                if($this->data->update($request->all())){
                    $this->s_msg = 'Client has been updated successfully';
                    $this->responsee(true);
                }
                else
                    $this->responsee(false, $this->w_err);
            }else
                $this->responsee(false, $this->d_err);
        }
        return $this->json_response($this->resp, $this->httpCode);
    }

    public function delete($id)
    {
        if($id){
            $this->data = RenewableItemModel::find($id);
            if($this->data){
                if($this->data->delete()){
                    $this->s_msg = 'Client has been deleted successfully';
                    $this->responsee(true);
                }
                else
                    $this->responsee(false, $this->w_err);
            }else
                $this->responsee(false, $this->d_err);
        }else
            $this->responsee(false, $this->id_err);
        return $this->json_response($this->resp, $this->httpCode);
    }
    private function json_response($response = array(), $code = 200)
    {
        // var_dump($response);die;
        // return response(['a'=>'ahad','b'=>'bahadur','c'=>'chniot'],200);
        return response()->json($response, $code);
    }
}


// $songs = Song::orderBy('id', 'desc')->where('user_id', auth()->user()->id)->paginate($perPage);
            // $data = SongResource::collection($songs);
            // $this->data = $data->response()->getData(true);
            // $this->data = SongResource::collection($songs);
            /*$this->data = [
                'data' => $data,
                'pagination' => [
                    'total' => $songs->total(),
                    'per_page' => $songs->perPage(),
                    'current_page' => $songs->currentPage(),
                    'last_page' => $songs->lastPage(),
                    'from' => $songs->firstItem(),
                    'to' => $songs->lastItem(),
                ],
            ]; */


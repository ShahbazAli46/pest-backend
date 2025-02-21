<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    protected $resp = array();

    protected $s_status = true;
    protected $e_status = false;
    protected $s_code = 200;
    protected $e_code = 400;
    public $s_msg = 'Success! Operation Completed';
    protected $id_err = 'Error! ID missing';
    protected $d_err = 'Error! Data not found';
    protected $w_err = 'Error! Something went wrong, please try again';
    protected $s_arr = array();
    protected $e_arr = array();
    protected $data;
    protected $httpCode;
    public function __construct()
    {
        $this->s_arr = ['status'=>$this->s_status, 'code'=>$this->s_code, 'message'=>$this->s_msg, 'data'=>''];
        $this->e_arr = ['status'=>$this->e_status, 'code'=>$this->e_code, 'message'=>$this->w_err, 'data'=>''];
        $this->resp = $this->e_arr;
    }

    function responsee($signal = true, $msg = ''){
        if($signal){
            $this->s_arr['data'] = $this->data;
            // if($msg)
            $this->s_arr['message'] = $this->s_msg;
            $this->httpCode = $this->s_code;
            return $this->resp = $this->s_arr;
        }
        else{
            if($msg){
                if(is_array($msg))
                    $this->e_arr['message'] = $msg;
                else
                    $this->e_arr['message'] = [$msg];
            }else{
                $this->e_arr['message'] = [$this->w_err];
            }
            $this->httpCode = $this->e_code;
            return $this->resp = $this->e_arr;
        }
    }
}

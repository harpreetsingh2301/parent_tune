<?php
namespace App\ApiResponse;


/* 
 * This class is for sending response
 * 
 */
class V1
{
    /**
     * Return failure json response
     * @param string $msg
     * @param int $error_code
     * @return type
     */
    public static function returnFailure(string $msg,int $error_code=0){
        $response_data=['status'=>false,'error_msg'=>$msg,'error_code'=>$error_code];
        return response()->json($response_data);
    }
    
    /**
     * Send sucess json response
     * @param array $data
     * @param array $extra_param
     * @return type
     */
    public static function returnData(array $data,array $extra_param = []){
        $extra_param['data']=$data;
        $response_data=[
                        'status'=>true,
                        'output_params'=>$extra_param
                       ];
        return response()->json($response_data);
    }
} 
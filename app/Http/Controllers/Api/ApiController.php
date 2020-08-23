<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\ApiResponse\V1 as ApiResponse;





class ApiController extends Controller
{
    public function registerPageVisit(Request $request)
    {
       $validation_rules = array(
            'user_id' => 'required',
            'content_id' => 'required'
        );

        $validator = Validator::make(
            $request->all(),
            $validation_rules
        );

        if ($validator->fails()) {
            $errors=$validator->messages();
            return ApiResponse::returnFailure($errors->all()[0]);
        }
        $page_visit = new \App\Models\PageVisits();
        $page_visit->user_id = $request->user_id;
        $page_visit->item_id = $request->content_id;
        $page_visit->created_at = date('Y-m-d H:i:s');
        $page_visit->save();
        return ApiResponse::returnData([]);
    }
        
}
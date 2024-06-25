<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Result;
class ResultController extends Controller
{
     public function index(Request $request){
    	
        if(empty($request->json('month'))){
            $parameters=Result::where('userId',$request->json('userId'))->get();
        }
         if(!empty($request->json('month'))){
            $parameters=Result::where('userId',$request->json('userId'))->where('month',$request->json('month'))->get();
        }
    	return response()->json(['data'=>$parameters]);
    }
    public function store(Request $request){
        try {
            
            DB::beginTransaction();

            $form=new Result;
            $form->month=$request->json('month');
            $form->value=$request->json('value');
            $form->userId=$request->json('userId');
            $form->parameterId=$request->json('parameterId');
            $form->save();

            DB::commit();
            return response()->json(['message'=>'stored successfully','data'=>$form]);

        } catch (\Exception $e) {
            Log::error('Form store failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'Form store failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Param;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class ParamController extends Controller
{
    public function index(Request $request){
    	
        
            $parameters=Param::where('clientId',$request->json('clientId'))->get();
         
    	return response()->json(['data'=>$parameters]);
    }
    public function store(Request $request){
        try {
            
            DB::beginTransaction();

            $form=new Param;
            $form->name=$request->json('name');
            $form->value=$request->json('value');
            $form->clientId=$request->json('clientId');
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
    public function update(Request $request){
        try {
            
            DB::beginTransaction();

            $form=Param::find($request->json('parameterId'));
            if(!$form){
                return response()->json([
                        'message' => 'parameter does not exists',
                    ], 422);
            }
            if(!empty($request->json('name'))){
                $form->name=$request->json('name');
            }
            if(!empty($request->json('value'))){
                $form->value=$request->json('value');
            }
            if(!empty($request->json('clientId'))){
                $form->clientId=$request->json('clientId');
            }
            $form->save();

            DB::commit();

            return response()->json(['message'=>'updated successfully','data'=>$form]);

        } catch (\Exception $e) {
            Log::error('Form update failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'Form update failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    public function delete(Request $request){
    	if(empty($request->json('parameterId'))){
    		return response()->json([
                    'message' => 'All fields (parameterId) required',
                ], 422);
    	}
    	$form=Param::find($request->json('parameterId'));
    	if(!$form){
    		return response()->json([
                    'message' => 'parameter does not exists',
                ], 422);
    	}
    	$form->delete();
    	return response()->json(['message'=>'deleted successfully']);
    }
}

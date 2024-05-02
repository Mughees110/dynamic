<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Form;
use App\Models\Node;
use App\Models\Field;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class FormController extends Controller
{
    public function index(Request $request){
    	
        if(empty($request->json('nodeId'))){
            $forms=Form::all();
            foreach ($forms as $key => $value) {
                $value->setAttribute('node',Node::find($value->nodeId));
            }
        }
        if(!empty($request->json('nodeId'))){
            $forms=Form::where('nodeId',$request->json('nodeId'))->get();
            foreach ($forms as $key => $value) {
                $value->setAttribute('node',Node::find($value->nodeId));
            }
        }
    	return response()->json(['data'=>$forms]);
    }
    public function store(Request $request){
        try {
            if(empty($request->json('intervalValue'))||empty($request->json('title'))||empty($request->json('description'))||empty($request->json('createdBy'))){
                return response()->json([
                        'message' => 'All fields (title description createdBy intervalValue) required',
                    ], 422);
            }
            DB::beginTransaction();

            $form=new Form;
            $form->title=$request->json('title');
            $form->description=$request->json('description');
            $form->createdBy=$request->json('createdBy');
            $form->interval=$request->json('interval');
            $form->intervalValue=$request->json('intervalValue');
            $form->nodeId=$request->json('nodeId');
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
            if(empty($request->json('formId'))||empty($request->json('title'))||empty($request->json('description'))||empty($request->json('createdBy'))){
                return response()->json([
                        'message' => 'All fields (formId title description createdBy) required',
                    ], 422);
            }
            DB::beginTransaction();

            $form=Form::find($request->json('formId'));
            if(!$form){
                return response()->json([
                        'message' => 'form does not exists',
                    ], 422);
            }
            if(!empty($request->json('title'))){
                $form->title=$request->json('title');
            }
            if(!empty($request->json('description'))){
                $form->description=$request->json('description');
            }
            if(!empty($request->json('createdBy'))){
                $form->createdBy=$request->json('createdBy');
            }
            if(!empty($request->json('intervalValue'))){
                $form->intervalValue=$request->json('intervalValue');
            }
            if(!empty($request->json('interval'))){
                $form->interval=$request->json('interval');
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
    	if(empty($request->json('formId'))){
    		return response()->json([
                    'message' => 'All fields (formId) required',
                ], 422);
    	}
    	$form=Form::find($request->json('formId'));
    	if(!$form){
    		return response()->json([
                    'message' => 'form does not exists',
                ], 422);
    	}
    	$fields=Field::where('formId',$request->json('formId'))->get();
    	foreach ($fields as $key => $value) {
    		$value->delete();
    	}
    	$form->delete();
    	return response()->json(['message'=>'deleted successfully']);
    }


}

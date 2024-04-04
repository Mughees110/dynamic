<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Field;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class FieldController extends Controller
{
    public function index(Request $request){
    	$fields=Field::where('formId',$request->json('formId'))->get();
    	return response()->json(['data'=>$fields]);
    }
    public function store(Request $request){
        try {
            if(empty($request->json('formId'))||empty($request->json('singleOrArray'))){
                return response()->json([
                        'message' => 'All fields (formId singleOrArray) required',
                    ], 422);
            }
            DB::beginTransaction();

            if($request->json('singleOrArray')=="single"){
                $field=new Field;
                $field->formId=$request->json('formId');
                $field->type=$request->json('type');
                $field->label=$request->json('label');
                $field->dropdownOptions=$request->json('dropdownOptions');
                $field->dropdownSelection=$request->json('dropdownSelection');
                $field->checkboxOrRadioOptions=$request->json('checkboxOrRadioOptions');
                $field->save();
                DB::commit();
                return response()->json(['message'=>'stored successfully']);
            }
            if($request->json('singleOrArray')=="array"){
                if(empty($request->json('fields'))){
                    return response()->json([
                        'message' => 'All fields (fields (array)) required',
                    ], 422);
                }
                foreach ($request->json('fields') as $key => $f) {
                    $field=new Field;
                    $field->formId=$request->json('formId');
                    $field->type=$f['type'];
                    $field->label=$f['label'];
                    $field->dropdownOptions=$f['dropdownOptions'];
                    $field->dropdownSelection=$f['dropdownSelection'];
                    $field->checkboxOrRadioOptions=$f['checkboxOrRadioOptions'];
                    $field->save();
                }
                DB::commit();
                return response()->json(['message'=>'stored successfully']);
            }
            return response()->json([
                        'message' => 'singleOrArray can be only single or array',
                    ], 422);


        } catch (\Exception $e) {
            Log::error('Field store failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'Field store failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    public function update(Request $request){
        try {
            if(empty($request->json('fieldId'))){
                return response()->json([
                        'message' => 'All fields (fieldId) required',
                    ], 422);
            }
            $field=Field::find($request->json('fieldId'));
            if(!$field){
                return response()->json([
                        'message' => 'field does not exists',
                    ], 422);
            }
            DB::beginTransaction();

            if(!empty($request->json('type'))){
                $field->type=$request->json('type');
            }
            if(!empty($request->json('label'))){
                $field->label=$request->json('label');
            }
            if(!empty($request->json('dropdownOptions'))){
                $field->dropdownOptions=$request->json('dropdownOptions');
            }
            if(!empty($request->json('dropdownSelection'))){
                $field->dropdownSelection=$request->json('dropdownSelection');
            }
            if(!empty($request->json('checkboxOrRadioOptions'))){
                $field->checkboxOrRadioOptions=$request->json('checkboxOrRadioOptions');
            }
            $field->save();

            DB::commit();

            return response()->json(['message'=>'updated successfully','data'=>$field]);

        } catch (\Exception $e) {
            Log::error('Field update failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'Field update failed'.$e->getMessage(),
            ], 422);
        }
    	
    }
    public function delete(Request $request){
    	if(empty($request->json('fieldId'))){
    		return response()->json([
                    'message' => 'All fields (fieldId) required',
                ], 422);
    	}
    	$field=Field::find($request->json('fieldId'));
    	if(!$field){
    		return response()->json([
                    'message' => 'field does not exists',
                ], 422);
    	}

    	$field->delete();
    	return response()->json(['message'=>'deleted successfully']);
    }
}

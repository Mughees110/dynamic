<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Answer;
use App\Models\Field;
use App\Models\Record;
use Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
class AnswerController extends Controller
{
    public function index(Request $request){
    	if(empty($request->json('formId'))||empty($request->json('userId'))){
    		return response()->json([
                    'message' => 'All answers (formId userId) required',
                ], 422);
    	}
        $records=Record::where('userId',$request->json('userId'))->where('formId',$request->json('formId'))->get();
        foreach ($records as $key => $value) {
            $answers=Answer::where('recordId',$value->id)->get();
            foreach ($answers as $key => $valueA) {
                $valueA->setAttribute('field',Field::find($valueA->fieldId));
            }
            $value->setAttribute('answers',$answers);
        }
    	/**/
    	return response()->json(['data'=>$answers]);
    }
    public function store(Request $request){
        try {
            if(empty($request->json('singleOrArray'))||empty($request->json('formId'))||empty($request->json('userId'))){
                return response()->json([
                        'message' => 'All answers (formId userId singleOrArray) required',
                    ], 422);
            }
            DB::beginTransaction();

            if($request->json('singleOrArray')=="single"){
                $answer=new Answer;
                $answer->formId=$request->json('formId');
                $answer->fieldId=$request->json('fieldId');
                $answer->userId=$request->json('userId');
                $answer->answer=$request->json('answer');
                $answer->answers=$request->json('answers');
                $answer->save();
                DB::commit();
                return response()->json(['message'=>'stored successfully']);
            }
            if($request->json('singleOrArray')=="array"){
                if(empty($request->json('answersR'))){
                    return response()->json([
                        'message' => 'All answers (answers (array)) required',
                    ], 422);
                }
                $record=new Record;
                $record->formId=$request->json('formId');
                $record->date=Carbon::now()->toDateString();
                $record->time=Carbon::now()->toTimeString();
                $record->save();
                foreach ($request->json('answersR') as $key => $a) {
                    $answer=new Answer;
                    $answer->formId=$request->json('formId');
                    $answer->fieldId=$a['fieldId'];
                    $answer->userId=$request->json('userId');
                    $answer->answer=$a['answer'];
                    $answer->answers=$a['answers'];
                    $answer->recordId=$record->id;
                    $answer->save();
                }
                DB::commit();
                return response()->json(['message'=>'stored successfully']);
            }
            return response()->json([
                        'message' => 'singleOrArray can be only single or array',
                    ], 422);

        } catch (\Exception $e) {
            Log::error('Answer store failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'Answer store failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    public function uploadFile(){
        $image=Input::file("file");
        $path='';
        if(!empty($image)){
            $newFilename=time().$image->getClientOriginalName();
            $destinationPath='files';
            $image->move($destinationPath,$newFilename);
            $path='files/' . $newFilename;
        }
        return response()->json(['status'=>true,'data'=>$path]);

    }
    public function uploadFiles(){
        $files=Input::file("files");
        $paths=array();
        if(!empty($files)){
            foreach ($files as $key => $video) {
                if(!empty($video)){
                    $newFilename=time().$video->getClientOriginalName();
                    $destinationPath='files';
                    $video->move($destinationPath,$newFilename);
                    $picPath='files/' . $newFilename;
                    $paths[$key]=$picPath;
                }
            }
        }
        return response()->json(['status'=>true,'data'=>$paths]);
    }
}

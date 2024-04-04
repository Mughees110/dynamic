<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doc;
use App\Models\Statics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Input;
class DocController extends Controller
{
    public function index(Request $request){
    	if(empty($request->json('staticsId'))){
	    	$docs=Doc::all();
	    	foreach ($docs as $key => $value) {
	    		$value->setAttribute('static',Statics::find($value->static));
	    	}
    	}
    	if(!empty($request->json('staticsId'))){
	    	$docs=Doc::where('static',$request->json('staticsId'))->get();
            foreach ($docs as $key => $value) {
                $value->setAttribute('static',Statics::find($value->static));
            }
    	}
    	return response()->json(['data'=>$docs]);
    }
    
    public function store(Request $request){
        try {
            if(empty($request->get('staticsId'))){
                return response()->json([
                        'message' => 'All fields (staticsId) required',
                    ], 422);
            }
            DB::beginTransaction();

            $image=Input::file("file");
	        $path='';
	        if(!empty($image)){
	            $newFilename=time().$image->getClientOriginalName();
	            $destinationPath='files';
	            $image->move($destinationPath,$newFilename);
	            $path='files/' . $newFilename;
	            $doc=new Doc;
	            $doc->static=$request->get('staticsId');
	            $doc->file=$path;
                $doc->usersIds=$request->get('usersIds');
	            $doc->save();
	        }
	        $files=Input::file("files");
	        $paths=array();
	        if(!empty($files)){
	            foreach ($files as $key => $video) {
	                if(!empty($video)){
	                    $newFilename=time().$video->getClientOriginalName();
	                    $destinationPath='files';
	                    $video->move($destinationPath,$newFilename);
	                    $picPath='files/' . $newFilename;
	                    $doc=new Doc;
			            $doc->static=$request->get('staticsId');
			            $doc->file=$picPath;
                        $doc->usersIds=$request->get('usersIds');
			            $doc->save();
	                }
	            }

	        }

            DB::commit();
            return response()->json(['message'=>'stored successfully']);

        } catch (\Exception $e) {
            Log::error('Form store failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'Form store failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    
    public function delete(Request $request){
    	if(empty($request->json('docId'))){
    		return response()->json([
                    'message' => 'All fields (docId) required',
                ], 422);
    	}
    	$doc=Doc::find($request->json('docId'));
    	if(!$doc){
    		return response()->json([
                    'message' => 'doc does not exists',
                ], 422);
    	}
    	$doc->delete();
    	return response()->json(['message'=>'deleted successfully']);
    }
}

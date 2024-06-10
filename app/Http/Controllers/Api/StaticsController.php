<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Statics;
use App\Models\Node;
use App\Models\Doc;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Input;
class StaticsController extends Controller
{
    public function index(Request $request){
     	if(empty($request->json('parentId'))){
    		$statics=Statics::all();
    	}
    	if(!empty($request->json('parentId'))){
    		$statics=Statics::where('parentId',$request->json('parentId'))->get();
    	}
    	foreach ($statics as $key => $value) {
    		if(!empty($value->parentId)){
    			$value->setAttribute('parent',Statics::find($value->parentId));
    		}
    	}
    	return response()->json(['data'=>$statics]);
    }
    public function index2(Request $request){

        if(!empty($request->json('clientId'))){
            $statics=Statics::where('parentId',null)->where('clientId',$request->json('clientId'))->get();
        }
        if(!empty($request->json('userId'))){
            $user=User::find($request->json('userId'));
            $statics=null;
            if(!empty($request->json($user->preference))){
                $statics=Statics::where('parentId',null)->whereIn('id',$user->preference)->get();
            }
        }
        
        return response()->json(['data'=>$statics]);
    }
    public function store(Request $request){
        try {
            if(empty($request->get('name'))){
                return response()->json([
                        'message' => 'All fields (name) required',
                    ], 422);
            }
            DB::beginTransaction();

            $statics=new Statics;
            $statics->name=$request->get('name');
            $statics->parentId=$request->get('parentId');
            $statics->clientId=$request->get('clientId');
            $image=Input::file("file");
	        $path='';
	        if(!empty($image)){
	            $newFilename=time().$image->getClientOriginalName();
	            $destinationPath='files';
	            $image->move($destinationPath,$newFilename);
	            $path='files/' . $newFilename;
	            $statics->file=$path;
	        }
            $statics->save();

            DB::commit();
            return response()->json(['message'=>'stored successfully','data'=>$statics]);

        } catch (\Exception $e) {
            Log::error('Statics store failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'Statics store failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    public function update(Request $request){
        try {
            if(empty($request->get('staticsId'))){
                return response()->json([
                        'message' => 'All fields (staticsId) required',
                    ], 422);
            }
            DB::beginTransaction();

            $statics=Statics::find($request->get('staticsId'));
            if(!$statics){
                return response()->json([
                        'message' => 'statics does not exists',
                    ], 422);
            }
            if(!empty($request->get('name'))){
                $statics->name=$request->get('name');
            }
            if(!empty($request->get('parentId'))){
                $statics->parentId=$request->get('parentId');
            }
            $image=Input::file("file");
	        $path='';
	        if(!empty($image)){
	            $newFilename=time().$image->getClientOriginalName();
	            $destinationPath='files';
	            $image->move($destinationPath,$newFilename);
	            $path='files/' . $newFilename;
	            $statics->file=$path;
	        }
	        
            $statics->save();

            DB::commit();

            return response()->json(['message'=>'updated successfully','data'=>$statics]);

        } catch (\Exception $e) {
            Log::error('Statics update failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'Statics update failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    public function delete(Request $request){
    	if(empty($request->json('staticsId'))){
    		return response()->json([
                    'message' => 'All fields (staticsId) required',
                ], 422);
    	}
    	$statics=Statics::find($request->json('staticsId'));
    	if(!$statics){
    		return response()->json([
                    'message' => 'statics does not exists',
                ], 422);
    	}
    	$statics->delete();
    	return response()->json(['message'=>'deleted successfully']);
    }
    public function info(Request $request){
        if(empty($request->json('staticsId'))){
            return response()->json([
                    'message' => 'All fields (staticsId) required',
                ], 422);
        }
        $isStatic=Statics::where('parentId',$request->json('staticsId'))->exists();
        $isNode=Node::where('static',$request->json('staticsId'))->exists();
        $isDoc=Doc::where('static',$request->json('staticsId'))->exists();
        return response()->json(['isStaticBelow'=>$isStatic,'isNodeBelow'=>$isNode,'isDocBelow'=>$isDoc]);

    }
}

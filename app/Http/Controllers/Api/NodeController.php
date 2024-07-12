<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Node;
use App\Models\Form;
use App\Models\Statics;
use App\Models\Record;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Input;
use Carbon\Carbon;

class NodeController extends Controller
{
     public function index(Request $request){
     	if(empty($request->json('parentId'))){
            if(empty($request->json('staticsId'))){
    		  $nodes=Node::all();
            }
            if(!empty($request->json('staticsId'))){
              $nodes=Node::where('static',$request->json('staticsId'))->get();
            }
    	}
    	if(!empty($request->json('parentId'))){
    		$nodes=Node::where('parentId',$request->json('parentId'))->get();
    	}
    	foreach ($nodes as $key => $value) {
    		if(!empty($value->parentId)){
    			$value->setAttribute('parent',Node::find($value->parentId));
    		}
            if(!empty($value->static)){
                $value->setAttribute('statics',Statics::find($value->static));
            }
            /*$bool=true;
            $pId=$value->id;
            //$value->setAttribute('count','1');
            if(!empty($request->json('userId'))){
                while($bool==true){
                    $existsC=Node::where('parentId',$pId)->exists();
                    if($existsC==true){
                        $n=Node::where('parentId',$pId)->first();
                        $pId=$n->id;
                    }
                    if($existsC==false){
                        $bool=false;
                        $form=Form::where('nodeId',$pId)->first();
                        if($form){
                            if($form->interval=="Weekly"){
                                $pcount=(int)$form->intervalValue;
                                $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
                                $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

                                $count = Record::where('userId',$request->json('userId'))->where('formId',$form->id)->whereBetween('date', [$startOfWeek, $endOfWeek])->count(); 
                                $r=$pcount-$count;
                                $$value->setAttribute('count',$r);

                            }
                            if($form->interval=="Monthly"){
                                $pcount=(int)$form->intervalValue;
                                $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
                                $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

                                $count = Record::where('userId',$request->json('userId'))->where('formId',$form->id)->whereBetween('date', [$startOfMonth, $endOfMonth])->count(); 
                                $r=$pcount-$count;
                                $value->setAttribute('count',$r);
                            }
                            if($form->interval=="Daily"){
                                $pcount=(int)$form->intervalValue;
                                $current = Carbon::now()->toDateString();
                                
                                $count = Record::where('userId',$request->json('userId'))->where('formId',$form->id)->where('date', $current)->count(); 
                                $r=$pcount-$count;
                                $value->setAttribute('count',$r);
                                
                            }
                        }

                    }
                }
            }*/
    	}
    	return response()->json(['data'=>$nodes]);
    }
    public function index2(Request $request){
        
        $nodes=Node::where('parentId',null)->get();
        
        return response()->json(['data'=>$nodes]);
    }
    public function store(Request $request){
        try {
            if(empty($request->get('name'))){
                return response()->json([
                        'message' => 'All fields (name) required',
                    ], 422);
            }
            DB::beginTransaction();

            $node=new Node;
            $node->name=$request->get('name');
            $node->parentId=$request->get('parentId');
            $node->value=$request->get('value');
            $image=Input::file("file");
	        $path='';
	        if(!empty($image)){
	            $newFilename=time().$image->getClientOriginalName();
	            $destinationPath='files';
	            $image->move($destinationPath,$newFilename);
	            $path='files/' . $newFilename;
	            
	        }
            $node->static=$request->get('staticsId');
	        $node->file=$path;
            $node->save();

            DB::commit();
            return response()->json(['message'=>'stored successfully','data'=>$node]);

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
            if(empty($request->get('nodeId'))){
                return response()->json([
                        'message' => 'All fields (nodeId) required',
                    ], 422);
            }
            DB::beginTransaction();

            $node=Node::find($request->get('nodeId'));
            if(!$node){
                return response()->json([
                        'message' => 'node does not exists',
                    ], 422);
            }
            if(!empty($request->get('name'))){
                $node->name=$request->get('name');
            }
            if(!empty($request->get('parentId'))){
                $node->parentId=$request->get('parentId');
            }
            if(!empty($request->get('value'))){
                $node->value=$request->get('value');
            }
            if(!empty($request->get('staticsId'))){
                $node->static=$request->get('staticsId');
            }
            $image=Input::file("file");
	        $path='';
	        if(!empty($image)){
	            $newFilename=time().$image->getClientOriginalName();
	            $destinationPath='files';
	            $image->move($destinationPath,$newFilename);
	            $path='files/' . $newFilename;
	            $node->file=$path;
	        }
	        
            $node->save();

            DB::commit();

            return response()->json(['message'=>'updated successfully','data'=>$node]);

        } catch (\Exception $e) {
            Log::error('Form update failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'Form update failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    public function delete(Request $request){
    	if(empty($request->json('nodeId'))){
    		return response()->json([
                    'message' => 'All fields (nodeId) required',
                ], 422);
    	}
    	$node=Node::find($request->json('nodeId'));
    	if(!$node){
    		return response()->json([
                    'message' => 'node does not exists',
                ], 422);
    	}
    	$node->delete();
    	return response()->json(['message'=>'deleted successfully']);
    }
    public function info(Request $request){
        if(empty($request->json('nodeId'))){
            return response()->json([
                    'message' => 'All fields (nodeId) required',
                ], 422);
        }
        $isNode=Node::where('parentId',$request->json('nodeId'))->exists();
        $isForm=Form::where('nodeId',$request->json('nodeId'))->exists();
        return response()->json(['isNodeBelow'=>$isNode,'isFormBelow'=>$isForm]);

    }
}

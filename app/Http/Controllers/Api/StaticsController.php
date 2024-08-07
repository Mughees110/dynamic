<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Statics;
use App\Models\Node;
use App\Models\Doc;
use App\Models\User;
use App\Models\Form;
use App\Models\Record;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Input;
use Carbon\Carbon;
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
            $pId = $value->id;
                
            // Find the deepest child in the Statics hierarchy
            while (Statics::where('parentId', $pId)->exists()) {
                $pId = Statics::where('parentId', $pId)->first()->id;
            }

            $node = Node::where('static', $pId)->first();
            if ($node && !empty($request->json('userId'))) {
                $pId2 = $node->id;

                // Find the deepest child in the Node hierarchy
                while (Node::where('parentId', $pId2)->exists()) {
                    $pId2 = Node::where('parentId', $pId2)->first()->id;
                }

                $form = Form::where('nodeId', $pId2)->first();
                if ($form) {
                    $pcount = (int)$form->intervalValue;
                    $userId = $request->json('userId');
                    $currentDate = Carbon::now();

                    switch ($form->interval) {
                        case "Weekly":
                            $startOfWeek = $currentDate->startOfWeek()->toDateString();
                            $endOfWeek = $currentDate->endOfWeek()->toDateString();
                            $count = Record::where('userId', $userId)
                                           ->where('formId', $form->id)
                                           ->whereBetween('date', [$startOfWeek, $endOfWeek])
                                           ->count();
                            break;

                        case "Monthly":
                            $startOfMonth = $currentDate->startOfMonth()->toDateString();
                            $endOfMonth = $currentDate->endOfMonth()->toDateString();
                            $count = Record::where('userId', $userId)
                                           ->where('formId', $form->id)
                                           ->whereBetween('date', [$startOfMonth, $endOfMonth])
                                           ->count();
                            break;

                        case "Daily":
                            $current = $currentDate->toDateString();
                            $count = Record::where('userId', $userId)
                                           ->where('formId', $form->id)
                                           ->where('date', $current)
                                           ->count();
                            break;

                        default:
                            $count = 0;
                            break;
                    }

                    $r = $pcount - $count;
                    if($r<0){
                        $r=0;
                    }
                    $value->setAttribute('count', $r);
                }
            }
    	}

    	return response()->json(['data'=>$statics]);
    }
    public function index2(Request $request){
        $statics=null;

        if(!empty($request->json('clientId'))){
            $statics=Statics::where('parentId',null)->where('clientId',$request->json('clientId'))->get();
        }
        if(!empty($request->json('userId'))){
            $user=User::find($request->json('userId'));
            $statics=null;
            if(!empty($user->preference)){
                $statics=Statics::where('parentId',null)->whereIn('id',$user->preference)->get();
            }
            if(empty($user->preference)){
                $statics=Statics::where('parentId',null)->get();
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Statics;
use App\Models\Node;
use App\Models\Doc;
use App\Models\User;
use App\Models\Form;
use App\Models\Field;
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
    public function copy(Request $request){
        if(empty($request->json('destinationClientId'))||empty($request->json('newClientId'))){
            return response()->json(['status'=>401,'message'=>'destinationClientId and newClientId required']);
        }
        $staticsAll=Statics::where('destinationClientId',$request->json('destinationClientId'))->where('parentId',null)->get();
        foreach ($staticsAll as $key => $sta) {
            $pId = $sta->id;
            $sta=Statics::find($pId);
            if($sta){
                DB::beginTransaction();

                $newsta=new Statics;
                $newsta->name=$sta->name;
                $newsta->parentId=null;
                $newsta->clientId=$request->json('clientId');
                $newsta->file=$sta->file;
                $newsta->save(); 

                $npId=$newsta->id;
                    
                // Find the deepest child in the Statics hierarchy
                while (Statics::where('parentId', $pId)->exists()) {

                    $st=Statics::where('parentId',$pId)->first();

                    $newSt=new Statics;
                    $newSt->name=$st->name;
                    $newSt->parentId=$npId;
                    $newSt->clientId=$request->json('clientId');
                    $newSt->file=$newSt->file;
                    $newSt->save();

                    $pId = Statics::where('parentId', $pId)->first()->id;
                    $npId=$newSt->id;
                }

                $node = Node::where('static', $pId)->first();
                if($node){
                    $pId2=$node->id;
                    $nnode=new Node;
                    $nnode->name=$node->name;
                    $nnode->value=$node->value;
                    $nnode->file=$node->file;
                    $nnode->parentId=null;
                    $nnode->static=$npId;
                    $nnode->save();
                    $npId2=$nnode->id;

                    while (Node::where('parentId', $pId2)->exists()) {

                        $nd=Node::where('parentId', $pId2)->first();
                        $nnd=new Node;
                        $nnd->name=$nd->name;
                        $nnd->value=$nd->value;
                        $nnd->file=$nd->file;
                        $nnd->parentId=$npId2;
                        $nnd->static=$npId2;
                        $nnd->save();

                        $pId2 = Node::where('parentId', $pId2)->first()->id;
                        $npId2=$nnd->id;
                    }

                    $form = Form::where('nodeId', $pId2)->first();
                    if($form){
                        $nform=new Form;
                        $nform->title=$form->title;
                        $nform->description=$form->description;
                        $nform->createdBy=null;
                        $nform->nodeId=$npId2;
                        $nform->intervalValue=$form->intervalValue;
                        $nform->interval=$form->interval;
                        $nform->save();
                    }

                }
                DB::commit();
            }
        }
        
        
        return response()->json(['status'=>200,'message'=>'stored successfully']);
    }

    public function copyStaticsAndNodes(Request $request)
    {
        if(empty($request->json('destinationClientId'))||empty($request->json('newClientId'))){
            return response()->json(['status'=>401,'message'=>'destinationClientId and newClientId required']);
        }
        DB::beginTransaction();
        try {
            $staticsAll = Statics::where('clientId', $request->json('destinationClientId'))
                                 ->where('parentId', null)
                                 ->get();

            foreach ($staticsAll as $sta) {
                $this->copyStaticAndChildren($sta, $request->json('newClientId'));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function copyStaticAndChildren(Statics $sta, $clientId)
    {
        // Copy the current static
        $newsta = new Statics;
        $newsta->name = $sta->name;
        $newsta->parentId = null;
        $newsta->clientId = $clientId;
        $newsta->file = $sta->file;
        $newsta->save();

        $newStaticId = $newsta->id;

        // Copy all children of the current static
        $this->copyChildren($sta->id, $newStaticId, $clientId);

        // Copy associated nodes
        $this->copyNodes($sta->id, $newStaticId);
    }

    public function copyChildren($parentId, $newParentId, $clientId)
    {
        $children = Statics::where('parentId', $parentId)->get();

        foreach ($children as $child) {
            $newChild = new Statics;
            $newChild->name = $child->name;
            $newChild->parentId = $newParentId;
            $newChild->clientId = $clientId;
            $newChild->file = $child->file;
            $newChild->save();

            $newChildId = $newChild->id;

            // Recursively copy children
            $this->copyChildren($child->id, $newChildId, $clientId);

            // Copy associated nodes
            $this->copyNodes($child->id, $newChildId);
        }
    }

    public function copyNodes($staticId, $newStaticId)
    {
        $nodes = Node::where('static', $staticId)->get();

        foreach ($nodes as $node) {
            $newNode = new Node;
            $newNode->name = $node->name;
            $newNode->value = $node->value;
            $newNode->file = $node->file;
            $newNode->parentId = null; // Will set this later
            $newNode->static = $newStaticId;
            $newNode->save();

            $newNodeId = $newNode->id;

            // Copy all child nodes
            $this->copyNodeChildren($node->id, $newNodeId);

            // Copy associated forms
            $this->copyForms($node->id, $newNodeId);
        }
    }

    public function copyNodeChildren($parentId, $newParentId)
    {
        $children = Node::where('parentId', $parentId)->get();

        foreach ($children as $child) {
            $newChild = new Node;
            $newChild->name = $child->name;
            $newChild->value = $child->value;
            $newChild->file = $child->file;
            $newChild->parentId = $newParentId; // Associate with the new parent node
            $newChild->static = $child->static; // Maintain the association with the original static
            $newChild->save();

            $newChildId = $newChild->id; // Correctly use this for forms

            // Recursively copy child nodes
            $this->copyNodeChildren($child->id, $newChildId);

            // Copy associated forms
            $this->copyForms($child->id, $newChildId);
        }
    }

    public function copyForms($nodeId, $newNodeId)
    {
        $forms = Form::where('nodeId', $nodeId)->get();

        foreach ($forms as $form) {
            $newForm = new Form;
            $newForm->title = $form->title;
            $newForm->description = $form->description;
            $newForm->createdBy = null; // Assuming you want to reset this
            $newForm->nodeId = $newNodeId; // Associate with the new node
            $newForm->intervalValue = $form->intervalValue;
            $newForm->interval = $form->interval;
            $newForm->save();

            $fields=Field::where('formId',$form->id)->get();
            foreach ($fields as $key => $field) {
                $newField=new Field;
                $newField->formId=$newForm->id;
                $newField->type=$field->type;
                $newField->label=$field->label;
                $newField->dropdownOptions=$field->dropdownOptions;
                $newField->dropdownSelection=$field->dropdownSelection;
                $newField->checkboxOrRadioOptions=$field->checkboxOrRadioOptions;
                $newField->save();
            }
        }
    }


}

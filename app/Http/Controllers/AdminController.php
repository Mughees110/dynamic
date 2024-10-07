<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\Form;
use App\Models\Field;
use App\Models\Node;
use App\Models\Statics;
use App\Models\Record;
use App\Models\Answer;
class AdminController extends Controller
{
    /*public function test(){
    	DB::statement('ALTER TABLE docs ADD static VARCHAR(255);');
    	dd('ok');
    }*/
    public function test(){
    	$recs=Record::where('userId',13)->get();
    	foreach ($recs as $key => $value) {
    		$ans=Answer::where('recordId',$value->id)->get();
    		foreach ($ans as $key => $value2) {
    			$value2->delete();
    		}
    		$value->delete();
    	}
    	dd('hi');
    	DB::statement('ALTER TABLE records ADD manu date;');
    	dd('hi');
    	DB::statement('ALTER TABLE statics ADD clientId VARCHAR(255);');
    	DB::statement('ALTER TABLE users ADD preference LONGTEXT;');
    	DB::statement('ALTER TABLE answers ADD recordId VARCHAR(255);');
    	dd('yes');
    	/*$questionTags = [['questionId' => '1', 'answers' => ["c", "d"]],['questionId' => '2', 'answers' => ["a", "b"]]];

		$events = [['_id'=>1,'eventName' => 'eve', 'questionTags' => [['questionId' => '1', 'answers' => ["c", "d"]],['questionId' => '2', 'answers' => ["a", "b"]]]],['_id'=>2,'eventName' => 'even2', 'questionTags' => [['questionId' => '1', 'answers' => ["c", "d"]],['questionId' => '2', 'answers' => ["a", "b"]]]]];*/
		/*$questionTags=$qts;
		$events=Event::all();
		$eIds = array();

		foreach ($events as $key => $event) {
		    $eventqts = $event['questionTags'];
		    
		    foreach ($eventqts as $key2 => $value) {
		    	
		        // Using "use" keyword to pass $value into the closure's scope
		        $result = [];

				foreach ($questionTags as $tag) {
				    if ($tag['questionId'] == $value['questionId']) {
				        $result = $tag;
				        break; // Exit the loop once a matching element is found
				    }
				}

		        if (!empty($result)) {
		            $answers=$result['answers'];
		            $intersection = array_intersect($answers, $value['answers']);
		            if (!empty($intersection)) {
		                array_push($eIds, $event['_id']);
		            }
		        }
		    }
		}

		// Output the event ids
		$eIds=array_unique($eIds);
		dd($eIds);*/
    }

     public function getStaticDetails(Request $request)
    {
        // Fetch the static record by ID
        $static = Statics::where('id', $request->json('staticId'))->first();

        if (!$static) {
            return response()->json(['error' => 'Static not found'], 404);
        }

        // Recursively fetch static hierarchy including nodes and forms
        $response = $this->fetchStaticHierarchy($static);

        return response()->json($response);
    }

    private function fetchStaticHierarchy($static)
    {
        $data = [
            'static_id' => $static->id,
            'static_name' => $static->name,
            'nodes' => [],
            'children' => []
        ];

        // Fetch nodes associated with this static ID that have no parent (top-level nodes)
        $nodes = Node::where('static', $static->id)
            ->whereNull('parentId')
            ->get();

        foreach ($nodes as $node) {
            // Fetch node hierarchy recursively
            $data['nodes'][] = $this->fetchNodeHierarchy($node);
        }

        // Fetch child statics recursively
        $childStatics = Statics::where('parentId', $static->id)->get();
        foreach ($childStatics as $childStatic) {
            $data['children'][] = $this->fetchStaticHierarchy($childStatic);
        }

        return $data;
    }

    private function fetchNodeHierarchy($node)
    {
        // Prepare node data
        $nodeData = [
            'node_id' => $node->id,
            'node_name' => $node->name,
            'forms' => [],
            'children' => []
        ];

        // Fetch forms associated with this node
        $forms = Form::where('nodeId', $node->id)->get();
        foreach ($forms as $form) {
        	$fields=Field::where('formId',$form->id)->get();
            $nodeData['forms'][] = [
                'form_id' => $form->id,
                'form_name' => $form->title,
                'fields'=>$fields
            ];
        }

        // Fetch child nodes recursively
        $childNodes = Node::where('parentId', $node->id)->get();
        foreach ($childNodes as $childNode) {
            $nodeData['children'][] = $this->fetchNodeHierarchy($childNode);
        }

        return $nodeData;
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
class AdminController extends Controller
{
    /*public function test(){
    	DB::statement('ALTER TABLE docs ADD static VARCHAR(255);');
    	dd('ok');
    }*/
    public function test(){
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
}

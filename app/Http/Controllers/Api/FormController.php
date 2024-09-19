<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Form;
use App\Models\Node;
use App\Models\Field;
use App\Models\Record;
use App\Models\Read;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
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
        if(!empty($request->json('userId'))){
            foreach ($forms as $key2 => $form) {
                $interval = $form->interval;
                $intervalValue = $form->intervalValue;
                $creationDate = Carbon::parse($form->created_at)->startOfDay(); // Parse form creation date

                $expectedOccurrences = 0; // Total expected occurrences since form creation
                $diff = 0; // Missing occurrences

                // Retrieve all the user's records for this form, grouped by date
                $records = Record::where('userId', $request->json('userId'))
                                ->where('formId', $form->id)
                                ->select('created_at')
                                ->get()
                                ->groupBy(function($record) {
                                    return Carbon::parse($record->created_at)->startOfDay()->toDateString();
                                });

                // Calculate occurrences based on the interval
                if ($interval == "Daily") {
                    $daysPassed = Carbon::now()->diffInDays($creationDate);

                    for ($i = 0; $i <= $daysPassed; $i++) {
                        $checkDate = $creationDate->copy()->addDays($i);
                        if (!$records->has($checkDate->toDateString())) {
                            $diff++; // Increment missing count if no record exists for this day
                        }
                    }
                } elseif ($interval == "Weekly") {
                    $weeksPassed = Carbon::now()->diffInWeeks($creationDate);

                    for ($i = 0; $i <= $weeksPassed; $i++) {
                        $checkDate = $creationDate->copy()->addWeeks($i)->startOfWeek();
                        $found = $records->filter(function ($record, $key) use ($checkDate) {
                            return Carbon::parse($key)->between($checkDate, $checkDate->copy()->endOfWeek());
                        });
                        if ($found->isEmpty()) {
                            $diff++; // Increment missing count if no record exists for this week
                        }
                    }
                } elseif ($interval == "Biweekly") {
                    $weeksPassed = Carbon::now()->diffInWeeks($creationDate);

                    for ($i = 0; $i <= $weeksPassed; $i += 2) {
                        $checkDate = $creationDate->copy()->addWeeks($i)->startOfWeek();
                        $found = $records->filter(function ($record, $key) use ($checkDate) {
                            return Carbon::parse($key)->between($checkDate, $checkDate->copy()->addWeeks(1)->endOfWeek());
                        });
                        if ($found->isEmpty()) {
                            $diff++; // Increment missing count if no record exists for this biweekly period
                        }
                    }
                } elseif ($interval == "Monthly") {
                    $monthsPassed = Carbon::now()->diffInMonths($creationDate);

                    for ($i = 0; $i <= $monthsPassed; $i++) {
                        $checkDate = $creationDate->copy()->addMonths($i)->startOfMonth();
                        $found = $records->filter(function ($record, $key) use ($checkDate) {
                            return Carbon::parse($key)->between($checkDate, $checkDate->copy()->endOfMonth());
                        });
                        if ($found->isEmpty()) {
                            $diff++; // Increment missing count if no record exists for this month
                        }
                    }
                } elseif ($interval == "Quarterly") {
                    $monthsPassed = Carbon::now()->diffInMonths($creationDate);

                    for ($i = 0; $i <= $monthsPassed; $i += 6) {
                        $checkDate = $creationDate->copy()->addMonths($i)->startOfMonth();
                        $found = $records->filter(function ($record, $key) use ($checkDate) {
                            return Carbon::parse($key)->between($checkDate, $checkDate->copy()->addMonths(5)->endOfMonth());
                        });
                        if ($found->isEmpty()) {
                            $diff++; // Increment missing count if no record exists for this quarter
                        }
                    }
                } elseif ($interval == "Yearly") {
                    $yearsPassed = Carbon::now()->diffInYears($creationDate);

                    for ($i = 0; $i <= $yearsPassed; $i++) {
                        $checkDate = $creationDate->copy()->addYears($i)->startOfYear();
                        $found = $records->filter(function ($record, $key) use ($checkDate) {
                            return Carbon::parse($key)->between($checkDate, $checkDate->copy()->endOfYear());
                        });
                        if ($found->isEmpty()) {
                            $diff++; // Increment missing count if no record exists for this year
                        }
                    }
                }

                // Add the missing count to the total sum
                $form->setAttribute('count',$diff);
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
    public function read(Request $request){
        if(empty($request->json('docId'))||empty($request->json('userId'))){
            return response()->json([
                    'message' => 'All fields (userId docId) required',
                ], 422);
        }
        $exists=Read::where('userId',$request->json('userId'))->where('docId',$request->json('docId'))->exists();
        if($exists==false){
            $read=new Read;
            $read->docId=$request->json('docId');
            $read->userId=$request->json('userId');
            $read->save();
        }
        return response()->json(['message'=>'read successfully']);

    }


}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Answer;
use App\Models\Field;
use App\Models\Record;
use App\Models\Form;
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
        if(empty($request->json('from'))&&empty($request->json('to'))){
            $records=Record::where('userId',$request->json('userId'))->where('formId',$request->json('formId'))->get();
        }
        if(!empty($request->json('from'))&&!empty($request->json('to'))){
            $records=Record::where('userId',$request->json('userId'))->where('formId',$request->json('formId'))->whereBetween('date',[$request->json('from'),$request->json('to')])->get();
        }
        foreach ($records as $key => $value) {
            $answers=Answer::where('recordId',$value->id)->get();
            foreach ($answers as $key => $valueA) {
                $valueA->setAttribute('field',Field::find($valueA->fieldId));
            }
            $value->setAttribute('answers',$answers);
        }
    	/**/
    	return response()->json(['data'=>$records]);
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
                $fill=null;

                $form=Form::find($request->json('formId'));
                if($form->interval=="Daily"){
                    $latestRecord = Record::where('userId', $request->json('userId'))
                    ->orderBy('date', 'desc')
                    ->first();
                    // Get today's date
                    $today = Carbon::now()->toDateString();
                    if ($latestRecord) {
                        $nextExpectedDate = Carbon::parse($latestRecord->date)->addDay()->toDateString();
                        if ($nextExpectedDate < $today) {
                            $fill=$nextExpectedDate;
                        }
                    }
                }
                if($form->interval=="Weekly"){
                    $latestRecord = Record::where('userId', $request->json('userId'))
                    ->orderBy('date', 'desc')
                    ->first();
                    // Get today's date
                    $today = Carbon::now()->toDateString();
                    if ($latestRecord) {
                        $nextExpectedWeek = Carbon::parse($latestRecord->date)->addWeek()->startOfWeek()->toDateString();
                        $currentWeekStart = Carbon::now()->startOfWeek()->toDateString();
                       if ($nextExpectedWeek < $currentWeekStart) {
                            $fill= $nextExpectedWeek;
                        }
                    }
                }
                if ($form->interval == "Biweekly") {
                    $latestRecord = Record::where('userId', $request->json('userId'))
                        ->orderBy('date', 'desc')
                        ->first();

                    $currentWeekStart = Carbon::now()->startOfWeek();
                    $currentWeekEnd = Carbon::now()->endOfWeek();
                    $missingWeeks = [];
                    if ($latestRecord) {
                        $lastSubmissionDate = Carbon::parse($latestRecord->date);
                        $weeksSinceLastSubmission = $lastSubmissionDate->diffInWeeks(Carbon::now());
                        // Iterate through the weeks between the last submission and now
                        for ($i = 1; $i <= $weeksSinceLastSubmission; $i++) {
                            $weekStart = $lastSubmissionDate->copy()->addWeeks($i)->startOfWeek();
                            $weekEnd = $lastSubmissionDate->copy()->addWeeks($i)->endOfWeek();

                            $recordsInWeek = Record::where('userId', $request->json('userId'))
                                ->whereBetween('date', [$weekStart, $weekEnd])
                                ->count();
                            if ($recordsInWeek < 2) {
                                $missingWeeks[] = $weekStart->toDateString(); // Add the start date of the missing week
                            }
                        }
                    }
                    if (count($missingWeeks) > 0) {
                        $fill=implode(' ', $missingWeeks);
                    }
                }
                if ($form->interval == "Monthly") {
                    $latestRecord = Record::where('userId', $request->json('userId'))
                        ->orderBy('date', 'desc')
                        ->first();

                    $currentMonthStart = Carbon::now()->startOfMonth();
                    $currentMonthEnd = Carbon::now()->endOfMonth();
                    $missingMonths = [];
                    if ($latestRecord) {
                        $lastSubmissionDate = Carbon::parse($latestRecord->date);

                        $monthsSinceLastSubmission = $lastSubmissionDate->diffInMonths(Carbon::now());

                        for ($i = 1; $i <= $monthsSinceLastSubmission; $i++) {
                            $monthStart = $lastSubmissionDate->copy()->addMonths($i)->startOfMonth();
                            $monthEnd = $lastSubmissionDate->copy()->addMonths($i)->endOfMonth();

                            $recordsInMonth = Record::where('userId', $request->json('userId'))
                                ->whereBetween('date', [$monthStart, $monthEnd])
                                ->count();

                            if ($recordsInMonth == 0) {
                                $missingMonths[] = $monthStart->toDateString(); // Add the start date of the missing month
                            }
                        }
                    }
                    // Output or return the missing months array
                    if (count($missingMonths) > 0) {
                        $fill=implode(' ', $missingMonths);
                    }
                }
                if ($form->interval == "Quarterly") {
                    $latestRecord = Record::where('userId', $request->json('userId'))
                        ->orderBy('date', 'desc')
                        ->first();

                    $currentYear = Carbon::now()->year;
                    $missingYears = [];

                    if ($latestRecord) {
                        $lastSubmissionYear = Carbon::parse($latestRecord->date)->year;
                        $yearsSinceLastSubmission = $currentYear - $lastSubmissionYear;

                        // Iterate through the years between the last submission and now
                        for ($i = 0; $i <= $yearsSinceLastSubmission; $i++) {
                            $yearToCheck = $lastSubmissionYear + $i;
                           // Check how many records exist for the current year being checked
                            $recordsInYear = Record::where('userId', $request->json('userId'))
                                ->whereYear('date', $yearToCheck)
                                ->count();
                             // If fewer than 2 records are found, consider it missing
                            if ($recordsInYear < 2) {
                                $missingYears[] = $yearToCheck; // Add the year to the missing years array
                            }
                        }
                    }

                    if (count($missingYears) > 0) {
                        $fill = implode(', ', $missingYears); // Join missing years for output
                    }
                }
                if ($form->interval == "Yearly") {
                    $latestRecord = Record::where('userId', $request->json('userId'))
                        ->orderBy('date', 'desc')
                        ->first();

                    $currentYear = Carbon::now()->year;
                    $missingYears = [];

                    if ($latestRecord) {
                        $lastSubmissionYear = Carbon::parse($latestRecord->date)->year;
                        $yearsSinceLastSubmission = $currentYear - $lastSubmissionYear;

                        for ($i = 0; $i <= $yearsSinceLastSubmission; $i++) {
                            $yearToCheck = $lastSubmissionYear + $i;

                            $recordsInYear = Record::where('userId', $request->json('userId'))
                                ->whereYear('date', $yearToCheck)
                                ->count();

                            if ($recordsInYear == 0) {
                                $missingYears[] = $yearToCheck; // Add the year to the missing years array
                            }
                        }
                    }

                    if (count($missingYears) > 0) {
                        $fill = implode(', ', $missingYears); // Join missing years for output
                    }
                }

                $record=new Record;
                $record->formId=$request->json('formId');
                $record->date=Carbon::now()->toDateString();
                $record->time=Carbon::now()->toTimeString();
                $record->userId=$request->json('userId');
                $record->fill=$fill;
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

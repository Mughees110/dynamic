<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\AnswerController;
use App\Http\Controllers\Api\DocController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\StaticsController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\ParameterController;
// Public routes (do not require authentication)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:120,1');
Route::post('/log', [AuthController::class, 'log'])->middleware('throttle:120,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:120,1');
Route::get('invalid',function(){
	 return response()->json(['message'=>'Access token not matched'],422);
})->name('invalid');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:120,1');

    Route::post('/forms', [FormController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/forms-store', [FormController::class, 'store'])->middleware('throttle:120,1');
    Route::post('/forms-update', [FormController::class, 'update'])->middleware('throttle:120,1');
    Route::post('/forms-delete', [FormController::class, 'delete'])->middleware('throttle:120,1');

    Route::post('/fields', [FieldController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/fields-store', [FieldController::class, 'store'])->middleware('throttle:120,1');
    Route::post('/fields-update', [FieldController::class, 'update'])->middleware('throttle:120,1');
    Route::post('/fields-delete', [FieldController::class, 'delete'])->middleware('throttle:120,1');

    Route::post('/answers', [AnswerController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/answers-store', [AnswerController::class, 'store'])->middleware('throttle:120,1');
    Route::post('/upload-file', [AnswerController::class, 'uploadFile'])->middleware('throttle:120,1');
    Route::post('/upload-files', [AnswerController::class, 'uploadFiles'])->middleware('throttle:120,1');

    Route::post('/nodes-parent', [NodeController::class, 'index2'])->middleware('throttle:120,1');
    Route::post('/nodes', [NodeController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/nodes-store', [NodeController::class, 'store'])->middleware('throttle:120,1');
    Route::post('/nodes-update', [NodeController::class, 'update'])->middleware('throttle:120,1');
    Route::post('/nodes-delete', [NodeController::class, 'delete'])->middleware('throttle:120,1');
    Route::post('/nodes-info', [NodeController::class, 'info'])->middleware('throttle:120,1');

    Route::post('/docs', [DocController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/docs-store', [DocController::class, 'store'])->middleware('throttle:120,1');
    Route::post('/docs-update', [DocController::class, 'update'])->middleware('throttle:120,1');
    Route::post('/docs-delete', [DocController::class, 'delete'])->middleware('throttle:120,1');

    Route::post('/users', [UserController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/users-store', [UserController::class, 'store'])->middleware('throttle:120,1');
    Route::post('/users-update', [UserController::class, 'update'])->middleware('throttle:120,1');
    Route::post('/users-delete', [UserController::class, 'delete'])->middleware('throttle:120,1');

    Route::post('/companies', [CompanyController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/companies-store', [CompanyController::class, 'store'])->middleware('throttle:120,1');
    Route::post('/companies-update', [CompanyController::class, 'update'])->middleware('throttle:120,1');
    Route::post('/companies-delete', [CompanyController::class, 'delete'])->middleware('throttle:120,1');

    Route::post('/statics-parent', [StaticsController::class, 'index2'])->middleware('throttle:120,1');
    Route::post('/statics', [StaticsController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/statics-store', [StaticsController::class, 'store'])->middleware('throttle:120,1');
    Route::post('/statics-update', [StaticsController::class, 'update'])->middleware('throttle:120,1');
    Route::post('/statics-delete', [StaticsController::class, 'delete'])->middleware('throttle:120,1');
    Route::post('/statics-info', [StaticsController::class, 'info'])->middleware('throttle:120,1');


    Route::post('/reports', [ReportController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/reports-store', [ReportController::class, 'store'])->middleware('throttle:120,1');
    Route::post('/reports-update', [ReportController::class, 'update'])->middleware('throttle:120,1');
    Route::post('/reports-delete', [ReportController::class, 'delete'])->middleware('throttle:120,1');


    Route::post('/parameters', [ParameterController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/parameters-store', [ParameterController::class, 'store'])->middleware('throttle:120,1');
    Route::post('/parameters-update', [ParameterController::class, 'update'])->middleware('throttle:120,1');
    Route::post('/parameters-delete', [ParameterController::class, 'delete'])->middleware('throttle:120,1');

    Route::post('/results', [ResultController::class, 'index'])->middleware('throttle:120,1');
    Route::post('/results-store', [ResultController::class, 'store'])->middleware('throttle:120,1');

});

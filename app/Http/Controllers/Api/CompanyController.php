<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
class CompanyController extends Controller
{
    public function index(Request $request){
     	$companies=Company::all();
    	
    	return response()->json(['data'=>$companies]);
    }
    public function store(Request $request){
        try {
            if(empty($request->json('email'))||empty($request->json('name'))){
                return response()->json([
                        'message' => 'All fields (email name) required',
                    ], 422);
            }
            DB::beginTransaction();

            $company=new Company;
            $company->name=$request->json('name');
            $company->email=$request->json('email');
            $company->expiryDate=$request->json('expiryDate');
            $company->save();

            DB::commit();
            return response()->json(['message'=>'stored successfully','data'=>$company]);

        } catch (\Exception $e) {
            Log::error('Company store failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'User store failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    public function update(Request $request){
        try {
            if(empty($request->json('companyId'))){
                return response()->json([
                        'message' => 'All fields (companyId) required',
                    ], 422);
            }
            DB::beginTransaction();

            $company=Company::find($request->json('companyId'));
            if(!$company){
                return response()->json([
                        'message' => 'company does not exists',
                    ], 422);
            }
            if(!empty($request->json('name'))){
                $company->name=$request->json('name');
            }
            if(!empty($request->json('email'))){
                $company->email=$request->json('email');
            }
            if(!empty($request->json('expiryDate'))){
                $company->expiryDate=$request->json('expiryDate');
            }
            
            $company->save();

            DB::commit();

            return response()->json(['message'=>'updated successfully','data'=>$company]);

        } catch (\Exception $e) {
            Log::error('Company update failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'Company update failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    public function delete(Request $request){
    	if(empty($request->json('companyId'))){
    		return response()->json([
                    'message' => 'All fields (companyId) required',
                ], 422);
    	}
    	$company=Company::find($request->json('companyId'));
    	if(!$company){
    		return response()->json([
                    'message' => 'company does not exists',
                ], 422);
    	}
    	$company->delete();
    	return response()->json(['message'=>'deleted successfully']);
    }
}

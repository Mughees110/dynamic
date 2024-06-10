<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
class UserController extends Controller
{
    public function index(Request $request){
     	if(empty($request->json('role'))){
    		$users=User::all();
    	}
    	if(!empty($request->json('role'))){
    		$users=User::where('role',$request->json('role'))->get();
    	}
    	return response()->json(['data'=>$users]);
    }
    public function store(Request $request){
        try {
            if(empty($request->json('email'))||empty($request->json('password'))){
                return response()->json([
                        'message' => 'All fields (email password) required',
                    ], 422);
            }
            DB::beginTransaction();

            $user=new User;
            $user->firstName=$request->json('firstName');
            $user->lastName=$request->json('lastName');
            $user->email=$request->json('email');
            $user->password=Hash::make($request->json('password'));
            $user->role=$request->json('role');
            $user->phone=$request->json('phone');
            $user->address=$request->json('address');
            $user->companyId=$request->json('companyId');
            $user->accessArray=$request->json('accessArray');
            $user->preference=$request->json('preference');
            $user->save();

            DB::commit();
            return response()->json(['message'=>'stored successfully','data'=>$user]);

        } catch (\Exception $e) {
            Log::error('User store failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'User store failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    public function update(Request $request){
        try {
            if(empty($request->json('userId'))){
                return response()->json([
                        'message' => 'All fields (userId) required',
                    ], 422);
            }
            DB::beginTransaction();

            $user=User::find($request->json('userId'));
            if(!$user){
                return response()->json([
                        'message' => 'user does not exists',
                    ], 422);
            }
            if(!empty($request->json('firstName'))){
                $user->firstName=$request->json('firstName');
            }
	        if(!empty($request->json('lastName'))){
                $user->lastName=$request->json('lastName');
            }
            if(!empty($request->json('email'))){
                $user->email=$request->json('email');
            }
            if(!empty($request->json('password'))){
                $user->password=Hash::make($request->json('password'));
            }
            if(!empty($request->json('role'))){
                $user->role=$request->json('role');
            }
            if(!empty($request->json('phone'))){
                $user->phone=$request->json('phone');
            }
            if(!empty($request->json('address'))){
                $user->address=$request->json('address');
            }
            if(!empty($request->json('endDateAccess'))){
                $user->endDateAccess=$request->json('endDateAccess');
            }
            if(!empty($request->json('companyId'))){
                $user->companyId=$request->json('companyId');
            }
            if(!empty($request->json('accessArray'))){
                $user->accessArray=$request->json('accessArray');
            }
            if(!empty($request->json('preference'))){
                $user->preference=$request->json('preference');
            }
            $user->save();

            DB::commit();

            return response()->json(['message'=>'updated successfully','data'=>$user]);

        } catch (\Exception $e) {
            Log::error('User update failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'User update failed'.$e->getMessage(),
            ], 422);
        }
    	
    	
    }
    public function delete(Request $request){
    	if(empty($request->json('userId'))){
    		return response()->json([
                    'message' => 'All fields (userId) required',
                ], 422);
    	}
    	$user=User::find($request->json('userId'));
    	if(!$user){
    		return response()->json([
                    'message' => 'user does not exists',
                ], 422);
    	}
    	$user->delete();
    	return response()->json(['message'=>'deleted successfully']);
    }
}

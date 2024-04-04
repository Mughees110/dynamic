<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Mail;
class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            if(empty($request->json('firstName'))||empty($request->json('lastName'))||empty($request->json('email'))||empty($request->json('password'))||empty($request->json('role'))){
                return response()->json([
                    'message' => 'All fields required',
                ], 422);
            }
            DB::beginTransaction();

            $user=new User;
            $user->firstName=$request->json('firstName');
            $user->lastName=$request->json('lastName');
            $user->email=$request->json('email');
            $user->password=Hash::make($request->json('password'));
            $user->role=$request->json('role');
            $user->save();
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json(['token' => $token, 'user' => $user]);

        } catch (\Exception $e) {
            Log::error('User registration failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'User registration failed'.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Authenticate the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            if(empty($request->json('email'))||empty($request->json('password'))){
                return response()->json([
                    'message' => 'All fields required',
                ], 422);
            }
            $user = User::where('email', $request->json('email'))->first();
            if (!$user || !Hash::check($request->json('password'), $user->password)) {
                return response()->json([
                    'message' => 'The credentials are invalid',
                ], 422);
            }
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json(['token' => $token, 'user' => $user]);

        } catch (\Exception $e) {
            // Log the error
            Log::error('User Login failed: ' . $e->getMessage());
            // Return a JSON response with an error message
            return response()->json([
                'message' => 'User login failed'.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    

    /**
     * Logout the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function forgotPassword(ForgotPassword $request){
        try {

            $validatedData = $request->validated();
            $email=$validatedData['email'];
            $exists=User::where('email',$validatedData['email'])->exists();
            if($exists==false){
                return response()->json([
                    'message' => 'Email does not belong to any user',
                ], 422);
            }
            $otp=rand(1111,8888);
            Mail::send('mail',['otp'=>$opt], function($message) use($email){
                     $message->to($email)->subject('BUS');
                     $message->from('bloodfor@blood-for-life.com');
                    });
            
            return response()->json(['otp' => $otp]);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Forgot Password Failed: ' . $e->getMessage());
            // Return a JSON response with an error message
            return response()->json([
                'message' => 'Unable to send email to your email address '.$e->getMessage(),
            ], 422);
        }
    }
    public function changePassword(ChangePassword $request){
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $exists=User::where('email',$validatedData['email'])->exists();
            if($exists==false){
                return response()->json([
                    'message' => 'Email does not belong to any user',
                ], 422);
            }
            $user=User::where('email',$validatedData['email'])->first();
            $user->password=Hash::make($validatedData['password']);
            $user->save();

            DB::commit();
            return response()->json(['message' => 'Password changed Successfully']);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Change Password Failed: ' . $e->getMessage());
            // Return a JSON response with an error message
            DB::rollBack();
            return response()->json([
                'message' => 'Unable to change password'.$e->getMessage(),
            ], 422);
        }
    }
    
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Notifications\ForgetPassword;
use App\Notifications\TwoFactorCode;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;
        
        $code = rand(100000, 999999);

        $user->update([
            'two_factor_code' => $code,
            'two_factor_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send the 2FA code to the user's email
        $user->notify(new TwoFactorCode($code));

        return response()->json(['token' => $token, 'user' => $user], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $token = Auth::user()->createToken('LaravelPassport')->accessToken;

            return response()->json(['token' => $token, 'name' => Auth::user()->name, 'profile_img' =>  Auth::user()->profile_img, 'user' => Auth::user()], 200);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function forgotEmail(Request $request){
        $user = User::where('email', '=', request()->input('email'))->first();
        
        if (!$user) {
            return response()->json(['message' => 'User Not Exist'], 200);
        }

        $input = $request->all();
        $token = Hash::make(mt_rand());
        $token = str_replace("/", "", $token);
        DB::table('password_reset_tokens')->insert([
          'email' => $input['email'],
          'token' => $token,
          'created_at' => Carbon::now()
        ]);
        
        $link ="https://evenupez.io/recover-password/" . $token;
        
        $user->notify(new ForgetPassword($user->name, $link));
        
        return response()->json(['message' => 'Recover link is sent to your Email!'], 200);
      }

      public function recoverEmail(Request $request){
          $input = $request->all();
        $res = DB::table('password_reset_tokens')
          ->where('token', $input['token'])
          ->orderby('created_at','desc')->get();
        if(sizeof($res) > 0){
          User::where('email', $res[0]->email)
            ->update([
              "password" => Hash::make($request['password'])
            ]);
            return response()->json(['message' => 'password changed'], 200);
        }
        else{
            return response()->json(['message' => 'Reset Password Token Not Found'], 200);
        }
      }

      public function logout(){
          $user = Auth::user();
          $user->token()->revoke();
          return response()->json(['message' => 'Successfully logged out'], 200);
      }

      public function generate2FA()
    {

        $user = Auth::user();

        // Generate a random 6-digit code
        $code = rand(100000, 999999);

        $user->update([
            'two_factor_code' => $code,
            'two_factor_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send the code via email
        $user->notify(new TwoFactorCode($code));

        return response()->json(['message' => '2FA code sent to your email.'], 200);
    }

    public function verify2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        if (!$user || $user->two_factor_code !== $request->code) {
            return response()->json(['message' => 'Invalid 2FA code.'], 400);
        }

        if (Carbon::now()->greaterThan($user->two_factor_expires_at)) {
            return response()->json(['message' => 'The 2FA code has expired.'], 400);
        }

        // Invalidate the code after verification
        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);

        return response()->json(['message' => '2FA code verified successfully.'], 200);
    }

}


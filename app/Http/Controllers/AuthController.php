<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Notifications\ForgetPassword;

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

            return response()->json(['token' => $token, 'name' => Auth::user()->name, 'profile_img' =>  Auth::user()->profile_img], 200);
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

}


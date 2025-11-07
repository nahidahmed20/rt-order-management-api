<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller {
    public function register(Request $request) {
        $user = User::create([
            'name' => $request->name,
            'email'=> $request->email,
            'password'=> Hash::make($request->password)
        ]);
        $token = JWTAuth::fromUser($user);
        return response()->json(compact('user','token'));
    }

    public function login(Request $request) {
        $credentials = $request->only('email','password');
        if(!$token = JWTAuth::attempt($credentials)){
            return response()->json(['error'=>'Invalid credentials'],401);
        }
        return response()->json(compact('token'));
    }

    public function logout() {
        auth()->logout();
        return response()->json(['message'=>'Logged out']);
    }
}


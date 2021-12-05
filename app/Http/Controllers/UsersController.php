<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\Roles;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    function create(Request $request)
    {
        /**
         * Get a validator for an incoming registration request.
         *
         * @param array $request
         * @return \Illuminate\Contracts\Validation\Validator
         */
        $valid = validator($request->only('email', 'name', 'password', 'phone_number','surname','second_name', 'oms'), [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'second_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|unique:users',
            'password' => 'required|string|min:6',
            'oms' =>'required|string|unique:users',
        ]);

        if ($valid->fails()) {
            $jsonError = response()->json($valid->errors()->all(), 400);
            return \Response::json($jsonError);
        }

        $data = request()->only('email', 'name', 'password', 'phone_number','surname','second_name','oms');

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'surname' => $data['surname'],
            'second_name' => $data['second_name'],
            'oms' => $data['oms'],
            'password' => bcrypt($data['password']),
        ]);

        Roles::create([
            'user' => $user->id,
            'role' => 'client'
        ]);

        return response()->json(["message"=>"create"], 201);
    }

    function login(Request $request){

        if(Auth::attempt(["email" => $request->email, "password" =>$request->password])){
            $client = Client::where('password_client', 1)->first();
            $user = Auth::user();
            $request->request->add([
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'username' => $request->email,
                'password' => $request->password,
                'scope' => $user->role()->first()->role,
            ]);
            var_dump($user->role()->first()->role);
            $token = Request::create(
                'oauth/token',
                'POST'
            );
            return \Route::dispatch($token);
        }
    }

    function refreshToken(Request $request){
        $client = Client::where('password_client', 1)->first();
        $token = $request->refresh_token;
        $request->request->add(
            [
                'grant_type' => 'refresh_token',
                'refresh_token' => $token,
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'scope' => '',
            ]
        );
        $token = Request::create(
            'oauth/token',
            'POST'
        );
        return \Route::dispatch($token);
    }

    function logout(){
        auth()->user()->tokens->each(function ($token, $key){
            $token->delete();
        });
        return response()->json(["message"=> "logout success"]);
    }

}

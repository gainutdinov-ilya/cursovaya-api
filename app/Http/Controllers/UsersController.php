<?php

namespace App\Http\Controllers;

use App\Models\Doctors;
use App\Models\Notes;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\Roles;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;


class UsersController extends Controller
{
    function create(Request $request)
    {

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
            return response()->json($valid->errors()->all(), 400);
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
                'scope' => $user->role()->role,
            ]);
            $token = Request::create(
                'oauth/token',
                'POST'
            );
            return \Route::dispatch($token);
        }
        else{
            return response()->json(["message" => "wrong credentials"], 401);
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
            if($token == Auth::user()->token()){
                $token->delete();
            }
        });
        return response()->json(["message"=> "logout success"]);
    }

    function update(Request $request){

        $valid = validator($request->only('email', 'name', 'password', 'phone_number','surname','second_name', 'oms'), [
            'name' => 'string|max:255',
            'surname' => 'string|max:255',
            'second_name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users',
            'phone_number' => 'string|unique:users',
            'password' => 'string|min:6',
            'oms' =>'string|unique:users',
        ]);

        if ($valid->fails()) {
            return response()->json($valid->errors()->all(), 400);
        }

        $user = $request->user();
        if(isset($request->name)) {
            $user->name = $request->input('name');
        }
        if(isset($request->surname)) {
            $user->surname = $request->input('surname');
        }
        if(isset($request->second_name))
            $user->second_name = $request->input('second_name');
        if(isset($request->oms))
            $user->oms = $request->input('oms');
        if(isset($request->phone_number))
            $user->phone_number = $request->input('phone_number');
        if(isset($request->email))
            $user->email = $request->input('email');
        if(isset($request->password))
            $user->password = bcrypt($request->input('password'));
        $user->save();
        return response()->json(["message"=>"updated"], 200);
    }

    function getUsers(Request $request){
        $users = User::all()->slice($request->offset, $request->limit);
        $answer = [];
        foreach ($users as $user){
            $role = $user->role()->role;
            $answer = array_merge($answer, array(array_merge($user->toArray(), ["role"=>$role]) ));
        }
        return response()->json($answer, 201);
    }

    function getUsersCount(){
        return response()->json(["count" =>  User::all()->count()], 201);
    }

    function getUserByID(Request $request){
        $user = User::where('id',$request->id)->first();
        $role = $user->role()->role;
        $speciality = null;
        if($role == 'doctor'){
            if($user->doctor() != null)
                $speciality = $user->doctor()->speciality;
        }
        return response()->json(array_merge($user->toArray(), ["role"=>$role, "speciality" => $speciality]), 200);
    }

    function updateUserByID(Request $request){
        $user = User::where('id',$request->id)->first();
        $role = $user->role();
        if(isset($request->name)) {
            $user->name = $request->input('name');
        }
        if(isset($request->surname)) {
            $user->surname = $request->input('surname');
        }
        if(isset($request->second_name))
            $user->second_name = $request->input('second_name');
        if(isset($request->oms))
            $user->oms = $request->input('oms');
        if(isset($request->phone_number))
            $user->phone_number = $request->input('phone_number');
        $user->save();
        if($role-> role == 'doctor' && $request->role != 'doctor'){
            $user->doctor()->delete();
        }
        $role->role = $request->role;
        if($request->role == 'doctor'){
            $doctor = $user->doctor();
            if($doctor == null){
                Doctors::create([
                    'user' => $user->id,
                    'speciality' => $request->speciality
                ]);
            }else{
                $doctor->speciality = $request->speciality;
                $doctor->save();
            }
        }
        $role->save();
        return response()->json(["message"=> "updated"], 204);
    }

    function createUserWithRole(Request $request)
    {

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
            'role' => $request->role
        ]);
        if($request->role == 'doctor'){
            Doctors::create([
                'user' => $user->id,
                'speciality' => $request->speciality
            ]);
        }

        return response()->json(["message"=>"create"], 201);
    }

    function delete(Request $request){
        $user = User::find($request->id);
        $user->delete();
    }

    function getDoctors(){
        $doctors = Doctors::all();
        $answer = [];
        foreach ($doctors as $doctor){
            $answer = array_merge($answer, array(array_merge($doctor->user()->toArray(), ["speciality" => $doctor->speciality])));
        }
        return response()->json($answer, 201);
    }

    function generateAlerts(){
        $user = Auth::user();
        $answer = [];
        if($user->isClient() || $user->isAdmin()){
            $notes = Notes::all()->where('client', '==', Auth::user()->id);
            if($notes != null){
                foreach ($notes as $note){
                    if($note->visited == false && $note->calendar()->dateTime > new \DateTime("now", new \DateTimeZone('Asia/Yekaterinburg'))){
                        $answer[] = array(
                            'title' => 'Запись',
                            'text' => "У вас имеется запись на ".$note->calendar()->dateTime->format("d-m-y").". Нажмите, чтобы ",
                            'action' => '/ticket/'.$note->id,
                            'action_title' => 'Получить талон'
                            );
                    }
                }
            }
        }
        if(count($answer) == 0){
            $answer = null;
        }
        return response()->json($answer, 201);
    }

}

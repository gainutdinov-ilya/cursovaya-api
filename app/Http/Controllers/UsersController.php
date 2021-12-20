<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
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

    function logout(){
        auth()->user()->tokens->each(function ($token, $key){
            if($token == Auth::user()->token()){
                $token->delete();
            }
        });
        return response()->json(["message"=> "logout success"]);
    }

    function logoutFromAll(){
        auth()->user()->tokens->each(function ($token, $key){
            if($token != Auth::user()->token()){
                $token->delete();
            }
        });
        return response()->json(["message"=> "logout success"]);
    }

    function update(Request $request){
        if($request->email != $request->user()->email) {
            $valid = validator($request->only('email', 'name', 'password', 'phone_number', 'surname', 'second_name'), [
                'email' => 'string|email|max:255|unique:users',
                'name' => 'string|max:255',
                'surname' => 'string|max:255',
                'second_name' => 'string|max:255',
                'phone_number' => 'string',
                'password' => 'string|min:6',
            ]);


            if ($valid->fails()) {
                return response()->json($valid->errors()->all(), 400);
            }
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
        return response()->json(["message"=>"updated"]);
    }


    function getUsers(Request $request){
        $users = User::all()->slice($request->offset, $request->limit);
        $answer = [];
        foreach ($users as $user){
            $role = $user->role()->role;
            $answer = array_merge($answer, array(array_merge($user->toArray(), ["role"=>$role]) ));
        }
        return response()->json($answer);
    }

    function getUsersCount(){
        return response()->json(["count" =>  User::all()->count()]);
    }

    function getUserByID(Request $request){
        $user = User::find($request->id);

        $role = $user->role()->role;
        $speciality = null;
        if($role == 'doctor'){
            if($user->doctor() != null)
                $speciality = $user->doctor()->speciality;
        }
        return response()->json(array_merge($user->toArray(), ["role"=>$role, "speciality" => $speciality]));
    }

    function updateUserByID(Request $request){
        $user = User::find($request->id);
        if($request->email != $user->email) {
            $valid = validator($request->only('email', 'name', 'password', 'phone_number', 'surname', 'second_name'), [
                'email' => 'string|email|max:255|unique:users',
                'name' => 'string|max:255',
                'surname' => 'string|max:255',
                'second_name' => 'string|max:255',
                'phone_number' => 'string',
                'password' => 'string|min:6',
            ]);


            if ($valid->fails()) {
                return response()->json($valid->errors()->all(), 400);
            }
        }
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
        if(isset($request->email))
            $user->email = $request->input('email');
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
        return response()->json(["message"=> "updated"]);
    }

    function createUserWithRole(Request $request)
    {

        $valid = \validator($request->only('email', 'name', 'password', 'phone_number','surname','second_name', 'oms'), [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'second_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|unique:users',
            'password' => 'required|string|min:6',
            'oms' =>'required|string|unique:users',
        ]);

        if ($valid->fails()) {
            return response()->json($valid->errors()->all(), 401);
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
        return response()->json($answer);
    }

    function generateAlerts(){
        $user = Auth::user();
        $answer = [];
        if($user->isClient()){
            $notes = Notes::all()->where('client', '==', Auth::user()->id);
            if($notes != null){
                foreach ($notes as $note){
                    if($note->visited == false && $note->calendar()->dateTime > new \DateTime("now", new \DateTimeZone('Asia/Yekaterinburg'))){
                        $answer[] = array(
                            'title' => 'Запись',
                            'text' => "У вас имеется запись на ".$note->calendar()->dateTime->format("d-m-y H:i").". Нажмите, чтобы ",
                            'action' => '/ticket/'.$note->id,
                            'action_title' => 'Получить талон'
                            );
                    }
                }
            }
        }elseif ($user->isAdmin()){
            $now = new \DateTime("now", new \DateTimeZone('Asia/Yekaterinburg'));
            $calendar = Calendar::all()->last();
            $doctors = Doctors::all();
            $count = [];
            foreach ($doctors as $doctor){
                $doc = $doctor->user();
                $tickets = Calendar::all()->where('dateTime', '>=', $now)->where('free', '==', 1)->where('doctor', '==', $doc->id)->sortBy('dateTime');
                $count_this = [
                    "doctor" => $doc,
                    "count" => count($tickets)
                ];
                $count[] = $count_this;
            }
            $count = collect($count)->sortByDesc("count")->last();
            if($count == null){
                return response()->json(null);
            }
            $answer[] = array(
                'title' => 'Напоминание',
                'text' => "В данный момент талоны присутствуют до ".$calendar->dateTime->format("d-m-y H:i")." Минимальное кол-во талонов составляет: ".$count["count"]." - ".$count["doctor"]["surname"]." ".mb_substr($count["doctor"]["name"],0,1,'UTF8').". ".mb_substr($count["doctor"]["second_name"],0,1,'UTF8').".",
                'action' => '/timeToRecord',
                'action_title' => 'Управление талонами'
            );
        }
        return response()->json($answer);
    }

    function searchUsers(Request $request){

        $search = $request->search;
        $search = mb_strtoupper(mb_substr($search, 0, 1, 'UTF8'), 'UTF8').mb_substr($search, 1, mb_strlen($search, 'UTF8'), 'UTF8');
        $users = User::where('surname','like',"%".$search."%")->get();
        $answer = [];
        foreach ($users as $user){
            $role = $user->role()->role;
            if($role == 'client' || Auth::user()->isAdmin()) {
                $answer = array_merge($answer, array(array_merge($user->toArray(), ["role" => $role])));
            }
        }
        if(count($answer) == 0)
            return response()->json(["message" => "not match"], 400);

        return response()->json($answer);
    }

    function updatePassword(Request $request){
        if(isset($request->id)){
            if(!Auth::user()->isAdmin() || !Auth::user()->isAdmin())
                return response()->json(["message" => "blocked"], 403);
            else{

                $user = User::find($request->id);
                $user->password = bcrypt($request->new_password);
                $user->save();
                return response()->json(["message" => "updated with personal/admin privileges"]);
            }
        }
        else if(\Hash::check($request->old_password, $request->user()->password)){

            $user = $request->user();
            $user->password = bcrypt($request->new_password);
            $user->save();
            return response()->json(["message" => "updated"]);
        }
        else {
            return response()->json(["message" => "wrong old password"], 400);
        }
    }

}

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CalendarController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $role = $user->role()->role;
        $speciality = null;
        if($role == 'doctor'){
            $speciality = $user->doctor()->speciality;
        }
        return response()->json(array_merge($user->toArray(), ["role"=>$role, "speciality" => $speciality]), 200);
    });
    Route::get('/alerts', [UsersController::class, 'generateAlerts']);
    Route::put('/user', [UsersController::class, 'update']);
    Route::delete('/logout', [UsersController::class, 'logout']);
    Route::delete('/logout/anywhere', [UsersController::class, 'logoutFromAll']);
    Route::get('/calendar', [CalendarController::class, 'getRelevant']);
    Route::post('/calendar/note', [CalendarController::class, 'createNote']);
    Route::get('/calendar/note', [CalendarController::class, 'getNote']);
    Route::delete('/calendar/note', [CalendarController::class, 'cancelNote']);
    Route::put('user/password', [UsersController::class, 'updatePassword']);
    Route::middleware('role:admin')->group(function (){
        Route::get('/users', [UsersController::class,'getUsers']);
        Route::get('/users/count', [UsersController::class, 'getUsersCount']);
        Route::post('/calendar', [CalendarController::class, 'generate'])->middleware('cors');
        Route::delete('/calendar', [CalendarController::class, 'delete']);
        Route::delete('/user', [UsersController::class, 'delete']);
    });
    Route::middleware('role:admin,doctor,personal')->group(function (){
        Route::get('/user/notes', [CalendarController::class, 'getNotes']);
        Route::get('/user/search', [UsersController::class, 'searchUsers']);
        Route::get('/user/id', [UsersController::class, 'getUserByID']);
        Route::put('/user/id', [UsersController::class, 'updateUserByID']);
        Route::post('/user', [UsersController::class, 'createUserWithRole']);
        Route::get('/doctors', [UsersController::class, 'getDoctors']);

    });
});

Route::post("/register", [UsersController::class, 'create']);
Route::post("/login", [UsersController::class,'login'])->name('login');


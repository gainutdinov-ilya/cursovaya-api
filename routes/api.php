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
    Route::put('/user', [UsersController::class, 'update']);
    Route::post('/logout', [UsersController::class, 'logout']);

    Route::middleware('role:admin')->group(function (){
        Route::get('/users', [UsersController::class,'getUsers']);
        Route::get('/usersCount', [UsersController::class, 'getUsersCount']);
        Route::post('/generateCalendar', [CalendarController::class, 'generate']);
    });
    Route::middleware('role:admin,doctor,personal')->group(function (){
        Route::get('/userByID', [UsersController::class, 'getUserByID']);
        Route::put('/userByID', [UsersController::class, 'updateUserByID']);
        Route::post('/user', [UsersController::class, 'createUserWithRole']);
    });
});

Route::post("/register", [UsersController::class, 'create']);
Route::post("/login", [UsersController::class,'login'])->name('login');
Route::post('/refresh', [UsersController::class, 'refreshToken']);


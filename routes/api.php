<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
// public routes

Route::get('data/destroy', [UserController::class, 'destroy']);
Route::get('data/show/{id}', [UserController::class, 'show']);
Route::resource('data',UserController::class);
Route::get('file/{file}/{token}', [UserController::class, 'preview']);
//Route::get('store', [UserController::class, 'store']);

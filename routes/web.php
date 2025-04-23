<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\IDUController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/',function (){
    
    return view('echo');
});
Route::post('add',[ApiController::class,'add']);
Route::get('/showName/{name}/{last}', function ($name,$last) {
   
    return view('htmll',compact($name,$last));
});
Route::get('/hellow/{name}',[ApiController::class,'telMeHellow']);
Route::resource('CAD',IDUController::class)->except(['index']);



Route::controller(ApiController::class)->group(function(){
Route::get('/hellow','telMeHellow');

Route::get('/allert','allert');

});


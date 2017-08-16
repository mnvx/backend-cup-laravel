<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test/tarantool', 'Test\TarantoolController@index');

// Users
Route::get('/users/{id}', 'Api\UserController@get');
Route::get('/users/{id}/visits', 'Api\UserController@getVisits');
Route::post('/users/new', 'Api\UserController@create');
Route::post('/users/{id}', 'Api\UserController@update');

// Locations
Route::get('/locations/{id}', 'Api\LocationController@get');
Route::get('/locations/{id}/avg', 'Api\LocationController@getAverage');
Route::post('/locations/new', 'Api\LocationController@create');
Route::post('/locations/{id}', 'Api\LocationController@update');

// Visits
Route::get('/visits/{id}', 'Api\VisitController@get');
Route::post('/visits/new', 'Api\VisitController@create');
Route::post('/visits/{id}', 'Api\VisitController@update');

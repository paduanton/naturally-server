<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
use App\Http\Resources\Users as UsersResource;
use App\Users;

Route::get('/teste', function () {
    return new UsersResource(Users::find(1));
});

Route::get('/', function () {
    return ['naturally-v1', date(DATE_ISO8601), env('APP_ENV')];
});

Route::group(['prefix' => '/v1'], function () use ($router) {
    $router->get('/', function () {
        return ['naturally-v1', date(DATE_ISO8601), env('APP_ENV')];
    });

    $router->get('/users', 'UsersController@index');
    $router->get('/users/{id}', 'UsersControllers@show');
});


Route::apiResource('/users', 'UsersController');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

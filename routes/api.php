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

Route::get('/', function () {
    return ['naturally-v1', date(DATE_ISO8601), env('APP_ENV')];
});

Route::group(['prefix' => '/v1'], function () use ($router) {
    $router->get('/', function () {
        return ['naturally-v1', date(DATE_ISO8601), env('APP_ENV')];
    });


    $router->post('/oauth/social', 'API\SocialAuthController@authenticate');


    Route::group(['middleware' => 'auth:api'], function () use ($router) {
        $router->apiResource('/users', 'API\UsersController');
    });
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

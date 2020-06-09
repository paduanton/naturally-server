<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return ['naturally-v1-web', now()->toDateTimeString(), config('app.env')];
});

Route::get('/oauth1/social/{provider}/redirect', 'API\SocialAuthController@redirectToProvider');
Route::get('/oauth1/social/{provider}/callback', 'API\SocialAuthController@handleProviderCallback');

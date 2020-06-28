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

Route::middleware('throttle:60,1')->get('/', function () {
    return ['naturally-v1-web', now()->toDateTimeString(), config('app.env')];
});

Route::group(['prefix' => '/oauth1/social', 'middleware' => 'throttle:60,1'], function () use ($router) {
    Route::get('/{provider}/redirect', 'API\SocialAuthController@redirectToProvider')->name('oauth1.redirect');
    Route::get('/{provider}/callback', 'API\SocialAuthController@handleProviderCallback')->name('oauth1.callback');
});

/*
    Terms of Use, Data Policy and Cookies Policy.
*/

Route::get('/legal/terms', 'TermsController@index')->name('terms');

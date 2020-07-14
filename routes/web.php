<?php

use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->get('/', function () {
    return ['naturally-web', now()->toDateTimeString(), config('app.env')];
});

Route::group(['prefix' => '/v1', 'middleware' => 'throttle:150|250,1'], function () use ($router) {
    Route::middleware('throttle:60,1')->get('/', function () {
        return ['naturally-v1-web', now()->toDateTimeString(), config('app.env')];
    });
    
    Route::group(['prefix' => '/oauth1/social', 'middleware' => 'throttle:60,1'], function () use ($router) {
        Route::get('/{provider}/redirect', 'API\SocialAuthController@redirectToProvider')->name('oauth1.redirect');
        Route::get('/{provider}/callback', 'API\SocialAuthController@handleProviderCallback')->name('oauth1.callback');
    });
    
    $router->get('/fallback', function () {
        return view('fallback');
    });

    $router->get('/notfound', function () {
        return view('notfound');
    });
    
    $router->get('/recipe/{id}/pdf', 'RecipeController@getRecipePDF');
});
/*
    Terms of Use, Data Policy and Cookies Policy.
*/

Route::get('/legal/terms', 'TermsController@index')->name('terms');

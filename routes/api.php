<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['naturally-v1', date(DATE_ISO8601), env('APP_ENV')];
});

Route::group(['prefix' => '/v1'], function () use ($router) {
    $router->get('/', function () {
        return ['naturally-api-v1', date(DATE_ISO8601), env('APP_ENV')];
    });

    $router->post('/oauth/social', 'API\SocialAuthController@authenticate');
    $router->post('/login', 'API\AuthController@login');
    $router->post('/signup', 'API\AuthController@signup');

    Route::group(['middleware' => 'auth:api'], function () use ($router) {
        $router->get('/logout', 'API\AuthController@logout');

        $router->apiResource('/users', 'API\UsersController');
        $router->get('/users', function (Request $request) {
            return $request->user();
        });

        $router->get('/recipes', 'API\RecipesController@index');
        $router->get('/recipes/{id}', 'API\RecipesController@show');
        $router->get('/users/{usersId}/recipes', 'API\RecipesController@getRecipesByUsersId');
        $router->post('/users/{usersId}/recipes', 'API\RecipesController@store');
        $router->put('/users/{usersId}/recipes/{id}', 'API\RecipesController@update');
        $router->delete('/users/{usersId}/recipes/{id}', 'API\RecipesController@destroy');

        $router->post('/recipes/{id}/upload/', 'API\RecipesImagesController@upload');
        $router->post('/recipes/{id}/upload/', 'API\RecipesImagesController@upload');
    });
});

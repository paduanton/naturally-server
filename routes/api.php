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

    /*
        Authentication Routes
    */
    $router->post('/oauth/social', 'API\SocialAuthController@authenticate');
    $router->post('/login', 'API\AuthController@login');
    $router->post('/signup', 'API\AuthController@signup');

    Route::group(['middleware' => 'auth:api'], function () use ($router) {
        $router->get('/logout', 'API\AuthController@logout');

        /*
            Users Routes
        */
        $router->apiResource('/users', 'API\UsersController');
        $router->get('/users', function (Request $request) {
            return $request->user();
        });

        /*
            Recipes Routes
        */
        
        $router->apiResource('/recipes', 'API\RecipesController');
        $router->get('/users/{usersId}/recipes', 'API\RecipesController@getRecipesByUsersId');
        $router->post('/users/{usersId}/recipes', 'API\RecipesController@store');

        /*
            RecipesImages Routes
        */
        
        $router->apiResource('/recipes/images', 'API\RecipesImagesController');
        $router->get('/recipes/{recipesId}/images', 'API\RecipesImagesController@index');
        $router->post('/recipes/{recipesId}/images', 'API\RecipesImagesController@upload');
        $router->patch('/recipes/{recipesId}/images/{id}', 'API\RecipesImagesController@update');

        $router->post('/users/{id}/upload/', 'API\UsersImagesController@upload');
    });
});

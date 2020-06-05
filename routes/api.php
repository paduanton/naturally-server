<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['naturally-api', date(DATE_ISO8601), env('APP_ENV')];
});

Route::group(['prefix' => '/v1'], function () use ($router) {
            /* -----  Unauthenticated Routes  ----- */

    $router->get('/', function () {
        return ['naturally-v1-api', date(DATE_ISO8601), env('APP_ENV')];
    });

    // Recipes

    $router->get('/recipes/{title}/search', 'API\RecipesController@search');
    $router->get('/recipes/{id}', 'API\RecipesController@show');

    // Users

    $router->get('/public/{login}/profile', 'API\UsersController@getPublicProfile');

    // Authentication Routes

    $router->post('/oauth/social', 'API\SocialAuthController@authenticate');
    $router->post('/login', 'API\AuthController@login');
    $router->post('/signup', 'API\AuthController@signup');
    
    //   Forgot Password
    
    $router->post('/forgot', 'API\ForgotPasswordController@forgot');
    $router->get('/forgot/{token}', 'API\ForgotPasswordController@getPasswordResetByToken');
    $router->patch('/forgot/{token}', 'API\ForgotPasswordController@resetPassword');

    //  Verify Email

    $router->patch('/verify/{id}', 'API\VerifyEmailController@validation');

    Route::group(['middleware' => 'auth:api'], function () use ($router) {
        /* -----  Authenticated Routes  ----- */

        /* 
            Authentication Routes
        */

        $router->get('/oauth/refresh/{token}', 'API\AuthController@getRefreshTokenInfo');
        $router->post('/oauth/refresh', 'API\AuthController@refreshToken');
        $router->post('/logout', 'API\AuthController@logout');

        /*
            Verify Email
        */

        $router->post('/user/{userId}/verify', 'API\VerifyEmailController@verify');
        $router->post('/verify/{id}/resend', 'API\VerifyEmailController@resendVerification');

        /*
            Users Routes
        */

        $router->get('/users/{name}/search', 'API\UsersController@search');
        $router->get('/user/{username}', 'API\UsersController@getByUsername');
        $router->put('/users/{id}', 'API\UsersController@update')->middleware('verified');
        $router->apiResource('/users', 'API\UsersController');
        $router->get('/user', function (Request $request) {
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

        $router->get('/images/{id}/recipes', 'API\RecipesImagesController@show');
        $router->delete('/images/{id}/recipes', 'API\RecipesImagesController@destroy');
        $router->get('/recipes/{recipesId}/images', 'API\RecipesImagesController@index');
        $router->post('/recipes/{recipesId}/images', 'API\RecipesImagesController@upload');
        $router->patch('/recipes/{recipesId}/images/{id}', 'API\RecipesImagesController@update');

        /*
            ProfileImages Routes
        */

        $router->get('/images/{id}/users', 'API\ProfileImagesController@show');
        $router->delete('/images/{id}/users', 'API\ProfileImagesController@destroy');
        $router->get('/users/{usersId}/images', 'API\ProfileImagesController@index');
        $router->post('/users/{id}/images/', 'API\ProfileImagesController@upload');
        $router->get('/users/{usersId}/images', 'API\ProfileImagesController@index');
        $router->get('/users/{usersId}/thumbnail', 'API\ProfileImagesController@getThumbnail');
        $router->patch('/users/{usersId}/images/{id}', 'API\ProfileImagesController@update');

        /*
            Followers Routes
        */

        $router->get('/users/{id}/followers', 'API\FollowersController@getFollowers');
        $router->get('/users/{id}/following', 'API\FollowersController@getFollowing');
        $router->get('/users/{id}/friends', 'API\FollowersController@getFriends');
        $router->get('/users/{firstUsersId}/mutual/{secondUsersId}/following', 'API\FollowersController@getMutualFollowing');
        $router->get('/users/{firstUsersId}/mutual/{secondUsersId}/followers', 'API\FollowersController@getMutualFollowers');
        $router->post('/users/{firstUsersId}/follow/{secondUsersId}', 'API\FollowersController@follow');
        $router->delete('/users/{firstUsersId}/unfollow/{secondUsersId}', 'API\FollowersController@unfollow');

        /*
            Ingredients Routes
        */

        $router->apiResource('/ingredients', 'API\IngredientsController');
        $router->get('/recipes/{recipesId}/ingredients', 'API\IngredientsController@getIngredientsByRecipesId');
        $router->post('/recipes/{recipesId}/ingredients', 'API\IngredientsController@store');

        /*
            Instructions Routes
        */

        $router->apiResource('/instructions', 'API\InstructionsController');
        $router->get('/recipes/{recipesId}/instructions', 'API\InstructionsController@getInstructionsByRecipesId');
        $router->post('/recipes/{recipesId}/instructions', 'API\InstructionsController@store');

        /*
            SocialNetworkAccounts Routes
        */

        $router->apiResource('/social', 'API\SocialNetworkAccountsController');
        $router->get('/users/{usersId}/social', 'API\SocialNetworkAccountsController@getSocialNetworksByUsersId');

        /*
            Comments Routes
        */

        $router->apiResource('/comments', 'API\CommentsController');
        $router->get('/recipes/{recipesId}/comments', 'API\CommentsController@getCommentsByRecipesId');
        $router->post('/users/{usersId}/recipes/{recipesId}/comments', 'API\CommentsController@store');

        /*
            Likes Routes
        */

        $router->apiResource('/likes', 'API\LikesController');
        $router->get('/user/{userId}/likes', 'API\LikesController@getLikesByUserId');
        $router->get('/recipes/{recipesId}/likes', 'API\LikesController@getLikesByRecipesId');
        $router->post('/users/{usersId}/recipes/{recipesId}/likes', 'API\LikesController@store');

        /*
            UsersFavoritesRecipes Routes
        */

        $router->apiResource('/favorites', 'API\UsersFavoritesRecipesController');
        $router->get('/recipe/{recipeId}/favorites', 'API\UsersFavoritesRecipesController@getFavoritesByRecipesId');
        $router->get('/user/{userId}/favorites', 'API\UsersFavoritesRecipesController@getFavoritesRecipesByUserId');
        $router->post('/user/{userId}/recipe/{recipeId}/favorite', 'API\UsersFavoritesRecipesController@store');

        /*
            Ratings Routes
        */

        $router->apiResource('/ratings', 'API\RatingsController');
        $router->get('/recipe/{recipeId}/ratings', 'API\RatingsController@getRatingsByRecipeId');
        $router->get('/user/{userId}/ratings', 'API\RatingsController@getRatingsByUserId');
        $router->post('/user/{userId}/recipe/{recipeId}/ratings', 'API\RatingsController@store');

        /*
            RatingsImages Routes
        */

        $router->get('/images/{id}/rating', 'API\RatingImageController@show');
        $router->delete('/images/{id}/rating', 'API\RatingImageController@destroy');
        $router->get('/rating/{ratingId}/images', 'API\RatingImageController@index');
        $router->post('/rating/{ratingId}/image', 'API\RatingImageController@upload');
        $router->patch('/rating/{ratingId}/image/{id}', 'API\RatingImageController@update');
    });
});

# Overview

Naturally is an open source project that implements social media and social networking concepts. It was developed in PHP using the Laravel Framework, MySQL database and MVC design pattern. This repository is a REST API backend only. The frontend is written in Angular 9 and can be seen here:

[Naturally Frontend](https://github.com/paduanton/naturally)

## Application Architecture
![](https://raw.githubusercontent.com/paduanton/naturally-server/master/public/docs/Application-Architecture.png)

This API has the following entities: Users, Recipes, ProfileImages, Ratings, RatingsImages, RecipesImages, SocialNetworkAccounts, Phones, Followers, Comments, UsersFavoriteRecipes, PasswordResets, RestoredAccounts, EmailVerifications, Likes, Reports, Instructions, Ingredients, RecipesTags, Tags, OAuthAuthCodes, OAuthAccessTokens, OAuthRefreshTokens, OAuthClients, OAuthPersonalAccessClients and Migrations.

#### Entity Relationship:
- Users 1 - N Recipes
- Users N - Followers - N Users
- Users 1 - N Phones
- Users 1 - N ProfileImages
- Users 1 - N SocialNetworkAccounts
- Users N - Likes - N Recipes
- Users N - Comments - N Recipes
- Users 1 - N Reports
- Users 1 - N PasswordResets
- Users 1 - N RestoredAccounts
- Users 1 - N EmailVerifications
- Users 1 - N OAuthClients
- Users N - UsersFavoriteRecipes - N Recipes
- Users N - OAuthAuthCodes - N OAuthClients
- Users N - OAuthAccessTokens - N OAuthClients
- OAuthAccessTokens 1 - 1 OAuthRefreshTokens
- OAuthPersonalAccessClients 1 - 1 OAuthClients
- Recipes N - RecipesTags - N Tags
- Recipes 1 - N RecipesImages
- Recipes 1 - N Instructions
- Recipes 1 - N Ingredients
- Migrations

The functionalities that cover this application are allowing the user to create an account, authenticate with social networks, create a recipe, add images to a recipe, embed an youtube video, download recipe in pdf format, add profile images, add rating to a recipe, comment on a recipe and reply to comments, follow other users, register phone number, like/dislike recipes, list recipes, add hashtag to recipes, restore user accounts and some more.

All available endpoints do the CRUD operations in all entities and relationships in database.

## ER Database Diagram
(click the image to zoom it or just download the image and zoom it by yourself so you can see better all tables relationships =D)
[![](https://raw.githubusercontent.com/paduanton/naturally-server/master/public/docs/ER-diagram.png)](https://raw.githubusercontent.com/paduanton/naturally-server/master/public/docs/ER-diagram.png)


## System Requirements (Mac OS, Windows or Linux)
* [Docker](https://www.docker.com/get-started)
* [Docker Compose](https://docs.docker.com/compose/install)


## Project Setup

Add the following line in the /etc/hosts of your system:
```
127.0.0.1       api.naturally.cooking
```

After clonning the repo, run the following commands on bash, inside the root directory:

Copy environment variables of the project:
```
cp .env.example .env
```
#### Notes: 
- Set the mail environment variables so you can receive emails sent through the application (in my case, I used mailtrap.io in dev environment)
- Also, set the Twitter oauth1 environment variables from your [developer account application](https://developer.twitter.com/), then you will be able to handle twitter login on this API


Build container and start development environment:
```
 docker-compose up --build
```

Install dependencies and set directory permissions and cache:
```
docker exec -it naturallyweb /bin/sh bootstrap.sh
```

To view changes in the database, go to http://api.naturally.cooking:8181/ on browser.

#### OAuth2 User Authentication:

In this API, through Laravel Framework it has been built OAuth2 authentication using the library [Passport](https://laravel.com/docs/7.x/passport), then it's possible to consume server side authentication using [JWT](https://jwt.io).

#### Notes:

In **./bootstrap.sh** file are all the commands required to build this project, so, in order to make any changes inside the container, or if you wish to run any other commands, you must run this script and update this file.

After all this steps, this project is running on port 80: http://api.naturally.cooking:80. All http requests send and receive JSON data.

## Authentication

In **all** endpoints you must have to make http requests with the header `Accept:application/json`. In http requests made with http verbs: POST, PUT, PATCH you need to set the header `Content-Type:application/json`.

To signup an user into our application, send a HTTP POST Request to `/v1/signup` with the json body:
```json
{
        "name": "Antonio de Pádua",
	"email" : "antonio.junior.h@gmail.com",
	"password" : "201125",
	"password_confirmation" : "201125",
	"birthday": "1999/09/22",
	"remember_me": true
}
```
To authenticate an existing user, send POST `/v1/login` with the data:

Send request with **username** or **email** field
```json
{
	"username" : "antonio.padua",
	"password" : "nheac4257",
	"remember_me": false
}
```

To signup or login an user with Facebook, Twitter or Google account in this application, send POST `/v1/oauth/social`

```json
{
	"provider": "twitter",
	"access_token" : "1273378-jk9z175IJWdF154gZCrIM6ZryY2Alk",
	"access_token_secret": "OBd4QjDpvhfpO8fj1YQPfC0c4bLXRQIaoS6wN52",
	"remember_me" : true
}
```
Ps: access_token_secret is required only when provider is twitter

On sucess, an user entity and auth resource will be returned with http code 200:
```json
{
    "id": 1000000,
    "name": "Antonio de Pádua",
    "username": "antonio.padua",
    "email": "antonio.junior.h@gmail.com",
    "email_verified_at": null,
    "birthday": "1999/09/22",
    "created_at": "2020-07-02 00:22:37",
    "updated_at": "2020-07-02 00:22:37",
    "auth_resource": {
        "token_type": "Bearer",
        "expires_in": "2021-08-02 00:22:44",
        "access_token": "eyJiJSUzI1NiJ9.eyJIiOivcGVzIjpbXX0.RbF_Gen0fI",
        "created_at": "2020-07-02 00:22:44",
        "refresh_token": "8cad9a8560f10d5270720?mFaMWmcnNRyX57x7u2smHnXlJW7Jc",
        "remember_token": "jpTynwS4d8daSSMmfM94XpetGjegs6iVE9myY896LOvojwKUe9V4tnKNM"
    }
}
```
Most of subsequent http requests must include this token in the HTTP header for user identification, so save it and sent it in all http requests. Header key will be Authorization with value 'Bearer' followed by a single space and then token string:

```
Authorization: Bearer eyJ0eXAiOiJKV1QiL.CJhbGciOiJSUzI1NiIm.p0aSI6Ic4ZDAwNG
```

#### Note:
To view routes that needs authentication go to files [./routes/web.php](https://github.com/paduanton/naturally-server/blob/master/routes/web.php) and [./routes/api.php](https://github.com/paduanton/naturally-server/blob/master/routes/api.php)

To get the authenticated user, send GET `/v1/user` with the access_token in the header:

```json
HTTP - 200

{
    "id": 6,
    "name": "Antonio de Pádua",
    "username": "antonio.padua",
    "email": "antonio.junior.h@gmail.com",
    "birthday": "1999-09-22",
    "created_at": "2020-05-03T06:17:20.000000Z",
    "updated_at": "2020-05-03T06:17:20.000000Z"
}
```

To create a Recipe, send POST `/v1/users/{userId}/recipes`:

```json
{
	"title": "Orange Cake",
	"description": "A very good cake",
	"cooking_time": "01:35:00",
	"category": "vegan",
	"meal_type": "breakfast",
	"youtube_video_url": "https://www.youtube.com/watch?v=_D0ZQPqeJkk",
	"yields": 2.5,
	"cost" : 1,
	"complexity": 5,
	"notes": "Good luck"
}
```
To get all Recipes, send GET `/v1/recipes` :

```json
HTTP - 200
{
    "data": [
        {
            "id": 2,
            "users_id": 1,
            "title": "Orange Cake",
            "description": "A very good cake",
            "cooking_time": "01:35:00",
            "category": "vegan",
            "meal_type": "breakfast",
            "youtube_video_url": null,
            "yields": 2.5,
            "cost": 1,
            "complexity": 5,
            "notes": "Good luck",
            "created_at": "2020-07-02 01:01:30",
            "updated_at": "2020-07-02 01:01:30"
        }
    ],
    "links": {
        "first": "http://api.naturally.cooking/v1/recipes?page=1",
        "last": "http://api.naturally.cooking/v1/recipes?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://api.naturally.cooking/v1/recipes",
        "per_page": 15,
        "to": 4,
        "total": 4
    }
}
```
- To get paginate data add query string: **?page=1**
- It's also possible to filter elements with the recipe attributes: 'cooking_time', 'meal_type', 'category', 'yields', 'cost', 'complexity'
like: **?category=vegan&meal_type=breakfast**
- Its also possible to filter elements with **order_by** and **limit** like: **?f_params[orderBy][field]=categoryd&f_params[orderBy][type]=desc&f_params[limit]=2**

To update a Recipe, send PUT `/v1/recipes/{recipeId}`:

```json
{
	"description": "A very good cake",
    "cooking_time": "01:35:00",
    "category": "vegan",
    "meal_type": "breakfast",
    "youtube_video_url": null,
    "yields": 2.5,
    "cost": 1,
}
```

To delete a Recipe,send DELETE `/v1/recipes/{recipeId}`:

```json
HTTP - 204
```

## Handling responses and errors

Part of the responses have already been exemplified, but here it will be explained the responses for each type of request and the errors for all of them.

#### HTTP POST

On success will be returned `HTTP CODE 201 - 200` with the body of the entity:

e.g:

```json
{
    "id": 4,
    "users_id": 1,
    "title": "Orange Cake",
    "description": "A very good cake",
    "cooking_time": "01:35:00",
    "category": "vegan",
    "meal_type": "breakfast",
    "youtube_video_url": "https://www.youtube.com/watch?v=_D0ZQPqeJkk",
    "yields": 2.5,
    "cost": 1,
    "complexity": 5,
    "notes": "Good luck",
    "created_at": "2020-07-02 01:08:11",
    "updated_at": "2020-07-02 01:08:11"
}
```

#### HTTP PUT - PATCH

On success will be returned `HTTP CODE 200` with the body of the entity:

```json
{
    "id": 4,
    "users_id": 1,
    "title": "Orange Cake",
    "description": "A very good cake",
    "cooking_time": "01:35:00",
    "category": "vegan",
    "meal_type": "breakfast",
    "youtube_video_url": "https://www.youtube.com/watch?v=_D0ZQPqeJkk",
    "yields": 2.5,
    "cost": 1,
    "complexity": 5,
    "notes": "Good luck",
    "created_at": "2020-07-02 01:08:11",
    "updated_at": "2020-07-02 01:08:11"
}
```

#### HTTP GET

On success will be returned  `HTTP CODE 200` with array of objects of the target model or just body with one object of the target model (e.g `/v1/recipes/{recipesId}`).

```json
[
    {
        "id": 4,
        "users_id": 1,
        "title": "Orange Cake",
        "description": "A very good cake",
        "cooking_time": "01:35:00",
        "category": "vegan",
        "meal_type": "breakfast",
        "youtube_video_url": "https://www.youtube.com/watch?v=_D0ZQPqeJkk",
        "yields": 2.5,
        "cost": 1,
        "complexity": 5,
        "notes": "Good luck",
        "created_at": "2020-07-02 01:08:11",
        "updated_at": "2020-07-02 01:08:11"
    }
]
```

#### HTTP DELETE

On success will be returned  `HTTP CODE 204`

### Erros

In case of the route needs authentication and your request does not have the access_token in the header, it will return:

```json
HTTP - 401
{
    "message": "Unauthenticated."
}
```

In case of your body is not formatted correctly:

```json
HTTP - 422
{
    "message": "The given data was invalid.",
    "errors": {
        "completed": [
            "The completed field must be true or false."
        ]
    }
}
```

In case the server can not find the info you are looking for: 

```json
HTTP - 404
{
    "message": "There is no data",
    "error": "Model not found in the server"
}
```

In case the server could not process the request properly: 

Ex:
```json
HTTP - 400
{
    "message": "could not delete data"
}
```

If the server gets an exception that has not been handled:

Ex:
```json
HTTP - 500
{
    "message": "ERROR TO HANDLE REQUEST",
    "error": "xxxxxx",
    "....": "....."
}
```

The application uses Soft Deletes to delete all of the info in HTTP DELETE requests, so when you do a **select** in database, the column **deleted_at** is not going to be null if the resource has been already deleted.

## Unit and Integration tests

Coming...

## Postman

If you use postman client, you can use the link below to import a **Collection** with most of the requests already documented. Currently the link has more than 110 http requests.

Just replace the auth header by your own token created in local environment.

https://www.getpostman.com/collections/b098ba6ee5df79e9ae01

## License

The Naturally project is open-sourced software licensed under the [MIT license](LICENSE.md).
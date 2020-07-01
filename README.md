# Overview

Naturally, it is an open source project that implements social media and social networking concepts. It was developed in PHP using the Laravel Framework and MySQL database with the MVC design pattern. This repository is the  backend  REST API only. The frontend is written in Angular 9 and can be seen here:

[Naturally Frontend](https://github.com/paduanton/naturally)

The entities that this API has are: Users, Recipes, ProfileImages, Ratings, RatingsImages, RecipesImages, SocialNetworkAccounts, Phones, Followers, Comments, UsersFavoriteRecipes, PasswordResets, RestoredAccounts, EmailVerifications, Likes, Reports, Instructions, Ingredients, RecipesTags, Tags, OAuthAuthCodes, OAuthAccessTokens, OAuthRefreshTokens, OAuthClients, OAuthPersonalAccessClients and Migrations. 

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

The functionalities that cover this application consist of allowing the user to create an account, authenticate with social networks, create a recipe, add images to a recipe, embed an youtube video, add profile images, add rating to a recipe, comment on a recipe and reply to comments, follow other users, register phone number, like/dislike recipes, list recipes, add hashtag to recipes, restore user accounts and more...

All available endpoints do the CRUD operations in all entities and relationships in database.

## ER database diagram
![](https://raw.githubusercontent.com/paduanton/naturally-server/master/public/docs/ER-diagram.png)

## System requirements (Mac OS, Windows or Linux)
* [Docker](https://www.docker.com/get-started)
* [Docker Compose](https://docs.docker.com/compose/install)


## Setup do projeto

Add the following line in the /etc/hosts of your system:
```
127.0.0.1       api.naturally.cooking
```

After clonning the repo, run the following commands on bash, inside the root directory:

Copy environment variables of the project:
```
cp .env.example .env
```

Build container and start development environment:
```
 docker-compose up --build
```

Install dependencies and set directory permissões and cache:
```
docker exec -it todosweb /bin/sh bootstrap.sh
```

To view changes in the database, go to http://api.naturally.cooking:8181/ on browser.

#### OAuth2 user authentication:

In this API, through Laravel Framework it has been built OAuth2 authentication using the library [Passport](https://laravel.com/docs/7.x/passport), then it's possible to consume server side authentication using [JWT](https://jwt.io).

#### Notes

In **./bootstrap.sh** file are all the commands required to build the project, so to make any changes inside the container you must run this script and update this file, if you wish do run any other commands. 

After all this steps, this project is running on port 80: http://api.naturally.cooking:80. All http requests send and receive JSON data.

## Authentication

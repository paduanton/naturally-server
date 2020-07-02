# Overview

Naturally, it is an open source project that implements social media and social networking concepts. It was developed in PHP using the Laravel Framework and MySQL database with the MVC design pattern. This repository is the  backend  REST API only. The frontend is written in Angular 9 and can be seen here:

[Naturally Frontend](https://github.com/paduanton/naturally)

## Application Architecture
![](https://raw.githubusercontent.com/paduanton/naturally-server/master/public/docs/Application-Architecture.png)

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

## ER Database Diagram
(click the image to zoom it or just download the image and zoom it by yourself so you can see better all tables relationships =D)
[![](https://raw.githubusercontent.com/paduanton/naturally-server/master/public/docs/ER-diagram.short.png)](https://raw.githubusercontent.com/paduanton/naturally-server/master/public/docs/ER-diagram.png)


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
#### Note: Set mail environment variables so you can receive emails sent through the application (in my case, I used mailtrap.io in dev environment)

Build container and start development environment:
```
 docker-compose up --build
```

Install dependencies and set directory permissions and cache:
```
docker exec -it todosweb /bin/sh bootstrap.sh
```

To view changes in the database, go to http://api.naturally.cooking:8181/ on browser.

#### OAuth2 User Authentication:

In this API, through Laravel Framework it has been built OAuth2 authentication using the library [Passport](https://laravel.com/docs/7.x/passport), then it's possible to consume server side authentication using [JWT](https://jwt.io).

#### Notes

In **./bootstrap.sh** file are all the commands required to build the project, so to make any changes inside the container you must run this script and update this file, if you wish do run any other commands. 

After all this steps, this project is running on port 80: http://api.naturally.cooking:80. All http requests send and receive JSON data.

## Authentication

Para **todos** endpoints é necessário fazer requisições com `header Accept:application/json` e `Content-Type:application/json` 

Para cadastrar um usuário, envie uma requisição POST para `/v1/signup` com os dados:
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
Para autenticar um usuário existente, envie uma requisição POST `/v1/login` com os dados:

Send request with **username** ou **email** field
```json
{
	"username" : "antonio.padua",
	"password" : "nheac4257",
	"remember_me": false
}
```

Em sucesso, um API access token será retornado com o tipo do token e a expiração dele:
```json
{
    "access_token": "eyJ0eXAiOiJKV1QiL.CJhbGciOiJSUzI1NiIm.p0aSI6Ic4ZDAwNG",
    "token_type": "Bearer",
    "expires_at": "2021-05-02 21:47:23"
}
```

Todas requisições subsequentes **devem incluir esse token no `cabeçalho HTTP` para identificação de usuários**. O indíce do cabeçalho deve ser `Authorization` com o valor **Bearer** seguido de espaço simples com o valor do token:
```
Authorization: Bearer eyJ0eXAiOiJKV1QiL.CJhbGciOiJSUzI1NiIm.p0aSI6Ic4ZDAwNG
```

Para buscar usuário autenticado, envie requisição GET para `/v1/user` somente com cabeçalho de autenticação e será retornado o seguinte response:

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

Para criar um Todo, envie requisição POST para `/v1/users/{userId}/todos` com os dados:

```json
{
	"title": "Uma tarefa",
	"description": "tarefa dos guri",
	"completed": 1
}
```
Para buscar todos os Todos, envie requisição GET para `/v1/todos` e será recebido o response:

```json
HTTP - 200

{
    "data": [
        {
            "id": 4,
            "users_id": 1,
            "title": "Uma tarefa",
            "description": "Lorem ipsum",
            "completed": 1,
            "images": [],
            "comments": [],
            "created_at": "2020-05-03T06:37:29.000000Z",
            "updated_at": "2020-05-03T06:37:29.000000Z"
        },
        {
            "id": 5,
            "users_id": 1,
            "title": "Segunda tarefa",
            "description": "Lorem ipsum 2",
            "completed": 1,
            "images": [],
            "comments": [],
            "created_at": "2020-05-03T06:40:52.000000Z",
            "updated_at": "2020-05-03T06:40:52.000000Z"
        }
    ],
    "links": {
        "first": "http://api.todos.social/v1/todos?page=1",
        "last": "http://api.todos.social/v1/todos?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://api.todos.social/v1/todos",
        "per_page": 15,
        "to": 2,
        "total": 2
    }
}
```
- Para páginar passe o argumento **?page=1**
- É possível filtrar Todos através dos atributos **completed** e **title** passando como argumentos: **?completed=1&title=Uma tarefa**

Para atualizar um Todo, envie requisição PUT para `/v1/todos/{todosId}` com os dados:

```json
{
	"title": "Lorem ipsum",
	"description": "É uma description",
	"completed": false
}
```

Para deletar um Todo, envie requisição DELETE para `/v1/todos/{todosId}` e receba o response:

```json
HTTP - 204
```

## Tratamento de responses e erros

Parte dos responses já foram exemplificados, mas aqui será explicado os responses para cada tipo de requisição e os erros para todos eles.

#### HTTP POST

Em caso de sucesso será retornado `HTTP CODE 201 - 200` com body do objeto da requisição

Ex:

```json
{
        "id": 4,
        "users_id": 1,
        "title": "Uma Tarefa",
        "description": "descrição tarefa",
        "completed": 1,
        "images": [],
        "comments": [],
        "created_at": "2020-05-03T06:37:29.000000Z",
        "updated_at": "2020-05-03T06:37:29.000000Z"
}
```

#### HTTP PUT - PATCH

Em caso de sucesso será retornado `HTTP CODE 200` com body do objeto da requisição

```json
{
        "id": 4,
        "users_id": 1,
        "title": "Uma Tarefa",
        "description": "descrição tarefa",
        "completed": 1,
        "images": [],
        "comments": [],
        "created_at": "2020-05-03T06:37:29.000000Z",
        "updated_at": "2020-05-03T06:37:29.000000Z"
}
```

#### HTTP GET

Em caso de sucesso será retornado `HTTP CODE 200` com um body de array de objetos do objeto alvo da requisição ou um body somente o objeto filtrado na requisição feitas (ex: `/v1/todos/{todosId}`).
```json
[
    {
        "id": 4,
        "users_id": 1,
        "title": "Uma Tarefa",
        "description": "descrição tarefa",
        "completed": 1,
        "images": [],
        "comments": [],
        "created_at": "2020-05-03T06:37:29.000000Z",
        "updated_at": "2020-05-03T06:37:29.000000Z"
    }
]
```

#### HTTP DELETE

Em caso de sucesso será retornado `HTTP CODE 204`

### Erros

Caso não possua token no cabeçalho será retornado um html informado exception de Route: login ou na maioria do casos, o seguinte:

```json
HTTP - 401
{
    "message": "Unauthenticated."
}
```

Caso o seu body não esteja formatado corretamente

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

Caso o servidor não consiga achar informações com a requisição passada 

```json
HTTP - 404
{
    "message": "There is no data",
    "error": "Model not found in the server"
}
```

Caso o servidor não consiga processar sua requisição 

Ex:
```json
HTTP - 400
{
    "message": "could not delete data"
}
```

Caso o servidor gere uma exception que não foi tratada

Ex:
```json
HTTP - 500
{
    "message": "ERROR TO HANDLE REQUEST",
    "error": "xxxxxx",
    "....": "....."
}
```

Lembrando que todas requisições **devem** conter o cabeçalho de autenticação com o token de usuário. Outro ponto a ser levantado é que é usado Soft Deletes para deletar informações, então ao consultar o banco de dados, a coluna **deleted_at** populada corresponde aos dados deletados das entidades.

## Testes Unitários

O código dos testes ficam no diretório /tests e para rodá-los use o comando:

```
docker exec -it todosweb php ./vendor/bin/phpunit
```

## Postman

Se você usa o postman, pode usar o link abaixo para importar uma **Collection** com grande parte das requisições da API. Atualmente o link contém 28 requisições documentadas.

Somente substitua o cabeçalho de autenticação pelo token gerado no seu ambiente local.

https://www.getpostman.com/collections/18009794791e5384e19a

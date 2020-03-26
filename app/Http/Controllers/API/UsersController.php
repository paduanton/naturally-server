<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Users;
use Illuminate\Http\Request;
use App\Http\Resources\Users as UsersResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UsersController extends Controller
{

    public function index()
    {
        if (Users::all()->isEmpty()) {
            throw new ModelNotFoundException;
        } 

        return UsersResource::collection(Users::all());
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        if (!Users::find($id)) {
            throw new ModelNotFoundException;
        }

        return new UsersResource(Users::find($id));
    
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Users  $users
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Users $users)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Users  $users
     * @return \Illuminate\Http\Response
     */
    public function destroy(Users $users)
    {
        //
    }
}

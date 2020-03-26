<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Users;
use Illuminate\Http\Request;
use App\Http\Resources\Users as UsersResource;
// use Illuminate\Database\Eloquent\ModelNotFoundException;
// throw new ModelNotFoundException('There is no User.');

class UsersController extends Controller
{
    
    public function index()
    {
        try {
            return UsersResource::collection(Users::all());
        } catch (\Exception $e) {
            abort(500);
        }
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
        try {
            if (empty(Users::find($id))) {
                abort(404);
            }
            var_dump(Users::find($id));
            return new UsersResource(Users::find($id));
        } catch (\Exception $e) {
            abort(500);
        }
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

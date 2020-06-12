<?php

namespace App\Http\Controllers\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\PhoneResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Phones;
use App\Users;

class PhoneController extends Controller
{

    public function show($id)
    {
        $phone = Phones::findOrFail($id);
        return new PhoneResource($phone);
    }

    public function getPhoneByUsersId($userId)
    {
        $user = Users::findOrFail($userId);
        $phones = $user->phones;

        if ($phones->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return PhoneResource::collection($phones);
    }

    public function store(Request $request, $userId)
    {
        $this->validate($request, [
            'label' => 'required|string',
            'number' => 'string|phone'
        ]);

        $user = Users::findOrFail($userId);
        $userPhones = $user->phones;
        
        if(sizeof($userPhones) >= 2) {
            return response()->json([
                'message' => 'max phone numbers exceeded (limit 2)'
            ], 400);
        }

        $phone = [
            'users_id' => $userId
        ];

        $phone = Phones::create($phone);

        if ($phone) {
            return new PhoneResource($phone);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }

    public function destroy($id)
    {
        $phone = Phones::findOrFail($id);
        $delete = $phone->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete phone',
        ], 400);
    }
}

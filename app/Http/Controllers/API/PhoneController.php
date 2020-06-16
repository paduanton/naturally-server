<?php

namespace App\Http\Controllers\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Propaganistas\LaravelPhone\PhoneNumber;
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

    public function getPhoneByUserId($userId)
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
            'number' => 'required|phone:AUTO,BR,mobile',
            'country' => 'required|string',
            'main' => 'required|boolean'
        ]);
        
        $user = Users::findOrFail($userId);
        $userPhones = $user->phones;
        $mainNumber = true;

        if (sizeof($userPhones) >= 2) {
            return response()->json([
                'error' => 'invalid phone number',
                'message' => 'max phone numbers exceeded (limit 2)'
            ], 400);
        }

        if ($userPhones->isEmpty() && !$request['main']) {
            return response()->json([
                'error' => 'invalid phone number',
                'message' => 'user must have at least one main phone number'
            ], 400);
        }

        $mainPhone = Phones::where('users_id', $userId)->where('main', true)->first();

        if($mainPhone && $request['main']) {
            return response()->json([
                'error' => 'invalid phone number',
                'message' => 'this user already have a main phone number'
            ], 400);
        }

        if(!PhoneNumber::make($request['number'])->isOfCountry($request['country'])) {
            return response()->json([
                'error' => 'invalid phone number',
                'message' => "provided phone number does not belong to this country {$request['country']}"
            ], 400);
        }
        
        $country = PhoneNumber::make($request['number'], 'BR')->getCountry();
        $formatedNumber = PhoneNumber::make($request['number'], 'BR')->formatForCountry($country);
        
        $isPhoneInDatabase = Phones::where('number', $formatedNumber)->first();

        if($isPhoneInDatabase) {
            return response()->json([
                'error' => 'invalid phone number',
                'message' => 'this phone is already registered in our database'
            ], 400);
        }

        $phone = [
            'users_id' => $userId,
            'label' => $request['label'],
            'main' => $mainNumber,
            'country_code' => $country,
            'number' => $formatedNumber
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

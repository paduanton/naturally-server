<?php

namespace App\Http\Controllers\API;

use App\RecipesImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class RecipesImagesController extends Controller
{

    public function upload(Request $request)
    {
        $basePath = 'uploads/images';
        $urlBasePath = url($basePath);
        $requestImages = $request->file('images');
        $usersId = Auth::user()->getAuthIdentifier();

        $image = new RecipesImages();
        $socialNetwork->provider_name = $provider;
        $socialNetwork->provider_id = $providerId;
        $socialNetwork->nickname = $nickname;
        $socialNetwork->profile_url = $profileUrl;
        $socialNetwork->picture_url = $pictureUrl;
        // $user = Users::firstOrCreate(['email' => $email], $userData);
        // $user->social_networks()->save($socialNetwork);

        $images = [
            'recipes' => null,
            'type' => $type,
            'thumbnail' => true,
            'picture_url' => '',
            'filename' => '',
            'original_filename' => '',
            'original_extension' => '',
            'mime' => ''
        ];

        foreach ($requestImages as $image) {
            if ($type === 'users_picture') {
                $urlBasePath = $urlBasePath . '/users';
                $store = $image->store($basePath . '/users', 'public');
            } else {
                $urlBasePath = $urlBasePath . '/recipes';
                $store = $image->store($basePath . '/recipes', 'public');
            }

            // $images['original_filename'] = $image->getClientOriginalName();
            // $image['mime'] = $image->getClientMimeType();
            // $image['original_extension] = $image->getClientOriginalExtension();
            // $image['filename'] = basename($store);
            var_dump($urlBasePath);
        }
    }

    protected function generateFilename($filename)
    {
        $filename = strtolower($filename);;

        $filename = str_replace(" ", "", $filename);
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);

        $image = Images::where('filename', $filename)->first();

        while ($image) {
            $randomNumber = mt_rand();
            $filename = $filename . $randomNumber;

            $image = Images::where('filename', $filename)->first();
        }

        return $image;
    }
}

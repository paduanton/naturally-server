<?php

namespace App\Services;

use App\Users;
use Exception;
use App\SocialNetWorks;
use App\UsersImages;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Laravel\Socialite\Facades\Socialite;
use League\OAuth2\Server\Exception\OAuthServerException;
use App\Services\Interfaces\SocialNetworksProviderInterface;

class SocialNetworksProvider implements SocialNetworksProviderInterface

{
    protected $userRepository;

    public function getUserEntityByAccessToken($provider, $accessToken, $accessTokenSecret)
    {
        $user = $this->getUserFromSocialProvider($provider, $accessToken, $accessTokenSecret);

        if (!$user) {
            return null;
        }

        return $user;
    }

    protected function getUserFromSocialProvider($provider, $accessToken, $accessTokenSecret)
    {
        try {
            if ($provider === 'twitter' && $accessTokenSecret) {
                $userFromProvider = Socialite::driver($provider)->userFromTokenAndSecret($accessToken, $accessTokenSecret);
            } else {
                $userFromProvider = Socialite::driver($provider)->stateless()->fields([
                    'first_name',
                    'middle_name',
                    'last_name',
                    'email'
                ])->userFromToken($accessToken);
            }
        } catch (Exception $exception) {
            // throw new OAuthServerException(
            //     'Authentication error, invalid access token',
            //     $errorCode = 401,
            //     'invalid_request'
            // );
            throw $exception;
        }

        var_dump($userFromProvider);
        return $this->findOrCreateSocialUser($userFromProvider, $provider);
    }

    protected function findOrCreateSocialUser($providerUser, $provider)
    {
        $socialAccount = SocialNetWorks::where('provider_name', $provider)
            ->where('provider_id', $providerUser->getId())
            ->first();

        if ($socialAccount) {
            return $socialAccount->users;
        }

        $firstName = $providerUser->user['first_name'];
        $middleName = $providerUser->user['middle_name'];
        $lastName = $providerUser->user['last_name'];
        $email = $providerUser->getEmail();
        $username = $providerUser->getNickname();
        $pictureURL = $providerUser->avatar_original;
        $providerId = $providerUser->getId();
        $profileURL = $providerUser->profileUrl;

        $socialNetwork = new SocialNetWorks();
        $socialNetwork->provider_name = $provider;
        $socialNetwork->provider_id = $providerId;
        $socialNetwork->username = $username;
        $socialNetwork->profile_url = $profileURL;
        $socialNetwork->picture_url = $pictureURL;

        if (!$username) {
            $username = $this->generateUsername($firstName, $lastName);
        }

        $userData = [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'username' => $username,
            'email' => $email,
        ];

        $user = Users::firstOrCreate(['email' => $email], $userData);
        $user->social_networks()->save($socialNetwork);
        $this->storeUsersPicture($pictureURL, $user);

        return $user;
    }

    protected function storeUsersPicture($pictureURL, $user)
    {
        $contents = file_get_contents($pictureURL);
        $tempFile = tempnam(sys_get_temp_dir(), 'naturally');
        file_put_contents($tempFile, $contents);
        $file = new UploadedFile($tempFile, $tempFile);

        $image = new UsersImages();
        $image->original_filename = $file->getClientOriginalName();
        $image->original_extension = 'jpeg';
        $image->mime = $file->getClientMimeType();

        $storeImage = $file->store('uploads/users/images', 'public');

        $image->filename = basename($storeImage);
        $image->path = $storeImage;
        $image->picture_url = url('storage/' . $image->path);
        $image->thumbnail = $this->getThumbnail($user);

        $user->images()->save($image);
        unlink($tempFile);
    }

    protected function getThumbnail($user)
    {
        $userHasThumbnail = UsersImages::where('thumbnail', true)->where('users_id', $user->id)->first();

        if (!$userHasThumbnail) {
            return true;
        }

        return false;
    }

    protected function generateUsername($firstName, $lastName)
    {
        $firstName = strtolower($firstName);
        $lastName = strtolower($lastName);
        $username = $firstName . "." . $lastName;

        $username = str_replace(" ", "", $username);
        $username = iconv('UTF-8', 'ASCII//TRANSLIT', $username);

        $user = Users::where('username', $username)->first();

        while ($user) {
            $randomNumber = mt_rand();
            $username = $username . $randomNumber;

            $user = Users::where('username', $username)->first();
        }

        return $username;
    }
}

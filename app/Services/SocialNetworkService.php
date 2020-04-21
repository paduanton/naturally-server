<?php

namespace App\Services;

use Exception;
use App\Users;
use App\ProfileImages;
use App\SocialNetWorks;
use Illuminate\Http\UploadedFile;
use Laravel\Socialite\Facades\Socialite;
use League\OAuth2\Server\Exception\OAuthServerException;
use App\Services\Interfaces\SocialNetworkServiceInterface;

class SocialNetworkService implements SocialNetworkServiceInterface
{

    public function getUserFromSocialProvider($provider, $accessToken, $accessTokenSecret): Users
    {
        try {
            if ($this->isOAuth1ProviderSupported($provider) && $accessTokenSecret) {
                $userFromProvider = $this->getUserEntityByAccessTokenAndSecret($provider, $accessToken, $accessTokenSecret);
            } else if ($this->isOAuth2ProviderSupported($provider)) {
                $userFromProvider = $this->getUserEntityByAccessToken($provider, $accessToken);
            }
        } catch (Exception $exception) {
            throw $exception;
        }

        return $this->findOrCreateSocialUser($userFromProvider, $provider);
    }

    public function getUserEntityByAccessToken($provider, $accessToken)
    {
        try {
            $userFromOAuth2 = Socialite::driver($provider)->stateless()->userFromToken($accessToken);
        } catch (OAuthServerException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw $exception;
        }

        return $userFromOAuth2;
    }

    public function getUserEntityByAccessTokenAndSecret($provider, $accessToken, $accessTokenSecret)
    {
        try {
            $userFromOAuth1 = Socialite::driver($provider)->userFromTokenAndSecret($accessToken, $accessTokenSecret);
        } catch (Exception $exception) {
            throw $exception;
        }

        return $userFromOAuth1;
    }

    public function isOAuth2ProviderSupported($provider)
    {
        return in_array($provider, ['facebook', 'google']);
    }

    public function isOAuth1ProviderSupported($provider)
    {
        return in_array($provider, ['twitter']);
    }

    protected function findOrCreateSocialUser($providerUser, $provider)
    {
        $socialAccount = SocialNetWorks::where('provider_name', $provider)
            ->where('provider_id', $providerUser->getId())
            ->first();

        if ($socialAccount) {
            return $socialAccount->users;
        }

        $name = $providerUser->getName();
        $email = $providerUser->getEmail();
        $username = $providerUser->getNickname();
        $providerId = $providerUser->getId();
        $pictureURL = $this->getAvatar($providerUser, $provider);
        $profileURL = $this->getProfileURL($providerUser, $provider);

        $socialNetwork = new SocialNetWorks();
        $socialNetwork->provider_name = $provider;
        $socialNetwork->provider_id = $providerId;
        $socialNetwork->username = $username;
        $socialNetwork->profile_url = $profileURL;
        $socialNetwork->picture_url = $pictureURL;

        if (!$username) {
            $username = $this->getOrgenerateUsername($name);
        } else {
            $username = $this->getOrgenerateUsername($username, $provider);
        }

        $userData = [
            'name' => $name,
            'username' => $username,
            'email' => $email,
        ];

        $user = Users::firstOrCreate(['email' => $email], $userData);
        $user->social_networks()->save($socialNetwork);
        $this->storeUsersPicture($pictureURL, $user);

        return $user;
    }

    protected function getAvatar($providerUser, $provider)
    {
        if ($provider === 'google') {
            $avatar = $providerUser->getAvatar();
        } else if ($provider === 'facebook') {
            $avatar = $providerUser->avatar_original;
        } elseif ($provider === 'twitter') {
            $avatar = str_replace('_normal', '', $providerUser->getAvatar());
            $avatar = str_replace('http', 'https', $avatar);
        }

        return $avatar;
    }

    protected function getProfileURL($providerUser, $provider)
    {
        if ($provider === 'twitter') {
            $profileURL = "https://twitter.com/{$providerUser->getNickname()}";
        } else if ($provider === 'facebook') {
            $profileURL = "https://facebook.com/{$providerUser->getId()}";
        } else {
            $profileURL = null;
        }

        return $profileURL;
    }

    protected function storeUsersPicture($pictureURL, $user)
    {
        $contents = file_get_contents($pictureURL);
        $tempFile = tempnam(sys_get_temp_dir(), 'naturally');
        file_put_contents($tempFile, $contents);
        $file = new UploadedFile($tempFile, $tempFile);

        $image = new ProfileImages();
        $image->original_filename = $file->getClientOriginalName();
        $image->original_extension = 'jpeg';
        $image->mime = $file->getClientMimeType();

        $storeImage = $file->store('uploads/users/images', 'public');

        $image->filename = basename($storeImage);
        $image->path = $storeImage;
        $image->picture_url = url('storage/' . $image->path);
        $image->thumbnail = $this->setThumbnail($user);

        $user->images()->save($image);
        unlink($tempFile);
    }

    protected function setThumbnail($user)
    {
        $userHasThumbnail = ProfileImages::where('thumbnail', true)->where('users_id', $user->id)->first();

        if (!$userHasThumbnail) {
            return true;
        }

        return false;
    }

    protected function getOrgenerateUsername($name, $provider = null)
    {
        if ($provider) {
            $username = $name;
        } else {
            $firstName = strtok($name, ' ');
            $firstName = strtolower($firstName);

            $lastName = strrchr($name, ' ');
            $lastName = strtolower($lastName);

            if (!$lastName) {
                $username = $firstName;
            } else {
                $username = $firstName . "." . $lastName;
            }

            $username = str_replace(" ", "", $username);
            $username = iconv('UTF-8', 'ASCII//TRANSLIT', $username);
        }

        $user = Users::where('username', $username)->first();

        while ($user) {
            $randomNumber = mt_rand();
            $username = $username . $randomNumber;

            $user = Users::where('username', $username)->first();
        }

        return $username;
    }
}

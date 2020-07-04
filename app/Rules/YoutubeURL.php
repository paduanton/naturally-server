<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class YoutubeURL implements Rule
{

    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        $parsedURL = parse_url($value);

        if (in_array($parsedURL['scheme'], ['https', 'http'])) {
            return true;
        } else {
            return false;
        }

        if (in_array($parsedURL['path'], ['watch', '/watch'])) {
            return true;
        } else {
            return false;
        }

        if (startsWith($parsedURL['query'], 'v=')) {
            return true;
        } else {
            return false;
        }

        if (endsWith($parsedURL['host'], 'youtube.com') || endsWith($parsedURL['host'], 'youtube.com.br')) {
            return true;
        }

        return false;
    }

    public function message()
    {
        return trans('validation.youtube_url');
    }

    public function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
}

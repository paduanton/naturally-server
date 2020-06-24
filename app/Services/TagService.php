<?php

namespace App\Services;

use App\Services\Interfaces\TagInterface;

class TagService implements TagInterface
{
    public function __construct()
    {
        //    
    }

    public function getHashtagPattern()
    {
        /*
            Allows only letters and numbers without empty space

            Regex: ^[a-zA-Z0-9_]*$
        */

        $pattern = "^[a-zA-Z0-9_]*$";
        $delimiter = "/";

        return $delimiter . $pattern . $delimiter;
    }

}

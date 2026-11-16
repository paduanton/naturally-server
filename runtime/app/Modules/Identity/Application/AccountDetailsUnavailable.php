<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final class AccountDetailsUnavailable extends \RuntimeException
{
    public function __construct() { parent::__construct('Account details are unavailable.'); }
}

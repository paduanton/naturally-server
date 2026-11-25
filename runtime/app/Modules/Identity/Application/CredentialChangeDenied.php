<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final class CredentialChangeDenied extends \RuntimeException
{
    public function __construct() { parent::__construct('Credential change is unavailable.'); }
}

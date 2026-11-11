<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

final class InactiveSession extends \RuntimeException
{
    public function __construct() { parent::__construct('The authenticated session is no longer active.'); }
}

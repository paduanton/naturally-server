<?php

declare(strict_types=1);

use App\Shared\Infrastructure\RuntimeApplicationFactory;

return RuntimeApplicationFactory::fromEnvironment(dirname(__DIR__));

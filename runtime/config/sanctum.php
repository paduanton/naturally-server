<?php

declare(strict_types=1);

$url = parse_url(config('app.url'));
return ['routes' => false, 'guard' => ['web'], 'stateful' => [$url['host'].(isset($url['port']) ? ':'.$url['port'] : '')], 'expiration' => null];

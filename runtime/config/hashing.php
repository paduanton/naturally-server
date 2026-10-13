<?php

declare(strict_types=1);

return [
    'driver' => 'argon2id',
    // Initial framework costs; production calibration belongs to Identity acceptance.
    'argon' => ['memory' => 65536, 'time' => 4, 'threads' => 1, 'verify' => true],
    'rehash_on_login' => true,
];

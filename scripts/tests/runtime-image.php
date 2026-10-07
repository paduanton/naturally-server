<?php

declare(strict_types=1);

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

try {
    check(PHP_VERSION_ID >= 80500 && PHP_VERSION_ID < 80600, 'PHP 8.5 is required.');
    foreach (['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl',
        'pcre', 'pdo', 'pdo_mysql', 'session', 'tokenizer', 'xml', 'bcmath', 'gd', 'intl',
        'redis', 'zip', 'xdebug'] as $extension) {
        check(extension_loaded($extension), 'Missing extension: '.$extension);
    }
    check(in_array('mysql', PDO::getAvailableDrivers(), true), 'The MySQL PDO driver is required.');

    $image = imagecreatetruecolor(2, 2);
    foreach (['imagejpeg', 'imagepng', 'imagewebp'] as $encoder) {
        check(function_exists($encoder), 'Missing image encoder: '.$encoder);
        ob_start();
        $encoded = $encoder($image);
        $bytes = ob_get_clean();
        check($encoded && $bytes !== false && $bytes !== '', 'Image encoding failed.');
        $decoded = imagecreatefromstring($bytes);
        check($decoded !== false && imagesx($decoded) === 2 && imagesy($decoded) === 2,
            'Image round trip failed.');
    }

    exec('composer --version --no-ansi 2>/dev/null', $composerOutput, $composerStatus);
    check($composerStatus === 0 && str_starts_with(implode("\n", $composerOutput), 'Composer version 2.'),
        'Composer 2 must execute under the default user.');
    check(function_exists('posix_geteuid') && posix_geteuid() !== 0, 'The default user must not be root.');
    check(! file_exists('/app/.env') && ! file_exists('/app/artisan'), 'Application files must not be baked into this tool image.');

    if (in_array('--coverage', $argv, true)) {
        check(in_array('coverage', xdebug_info('mode'), true), 'Coverage mode must be enabled explicitly.');
        $probePath = tempnam(sys_get_temp_dir(), 'naturally-branch-');
        check($probePath !== false, 'Cannot create the coverage probe.');
        try {
            $probeSource = '<?php return static function (bool $value): int { if ($value) { return 1; } return 0; };';
            check(file_put_contents($probePath, $probeSource) !== false, 'Cannot write the coverage probe.');
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);
            $probe = require $probePath;
            check($probe(true) === 1 && $probe(false) === 0, 'Coverage probe behavior failed.');
            $coverage = xdebug_get_code_coverage();
            xdebug_stop_code_coverage();
            $functions = $coverage[$probePath]['functions'] ?? [];
            $coveredPaths = 0;
            foreach ($functions as $function) {
                check(! empty($function['branches']), 'Branch coverage metadata is missing.');
                foreach ($function['paths'] ?? [] as $path) {
                    $coveredPaths += ($path['hit'] ?? 0) > 0 ? 1 : 0;
                }
            }
            check($coveredPaths >= 2, 'Both probe paths must appear in branch coverage.');
        } finally {
            unlink($probePath);
        }
    } else {
        check(xdebug_info('mode') === [], 'Xdebug must be disabled by default.');
    }
    fwrite(STDOUT, 'Runtime image checks passed (PHP '.PHP_VERSION.").\n");
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
}

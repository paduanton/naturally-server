param(
    [ValidateSet('up','test','down','status')][string]$Action = 'up',
    [switch]$TestEnvironment
)
$ErrorActionPreference = 'Stop'
$project = if ($Action -eq 'test' -or $TestEnvironment) { 'naturally-modern-tests' } else { 'naturally-modern' }
$previousDisableEnv = $env:COMPOSE_DISABLE_ENV_FILE
$previousEnvFiles = $env:COMPOSE_ENV_FILES
$env:COMPOSE_DISABLE_ENV_FILE = '1'
Remove-Item Env:COMPOSE_ENV_FILES -ErrorAction SilentlyContinue
Push-Location (Split-Path $PSScriptRoot -Parent)

function Compose {
    & docker compose -p $project -f compose.modern.yml @args
    if ($LASTEXITCODE -ne 0) { throw "Docker Compose failed (exit $LASTEXITCODE)." }
}

try {
    Compose config --quiet
    if ($Action -eq 'down') { Compose down; return }
    if ($Action -eq 'status') { Compose ps; return }
    if ($TestEnvironment -and $Action -eq 'up') { throw 'Use the test action to start isolated test services.' }
    Compose build tools
    Compose run --rm init
    Compose run --rm dependencies
    if ($Action -eq 'test') {
        Compose up -d --wait db redis
        Compose run --rm volume-tests
        Compose run --rm tools php vendor/bin/phpunit --no-coverage
        Compose run --rm -e NATURALLY_CONFIG_PATH=/app/settings/testing.json tools php vendor/bin/phpunit --configuration phpunit.services.xml --no-coverage
    } else {
        Compose up -d --wait db redis mailpit api
        Write-Host 'API: http://localhost:8080/health/live | Mailpit: http://localhost:8025'
    }
} finally {
    if ($Action -eq 'test') {
        # Keep volumes and credentials, including when preparation or a test fails.
        & docker compose -p $project -f compose.modern.yml down
        if ($LASTEXITCODE -ne 0) { Write-Warning 'Test containers could not be stopped; use down -TestEnvironment.' }
    }
    Pop-Location
    $env:COMPOSE_DISABLE_ENV_FILE = $previousDisableEnv
    $env:COMPOSE_ENV_FILES = $previousEnvFiles
}

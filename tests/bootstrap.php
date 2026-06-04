<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test bootstrap
|--------------------------------------------------------------------------
|
| Guarantee a valid APP_KEY for the test process before the framework boots.
| The CI workflow injects `APP_KEY: base64:$(openssl rand -base64 32)` as a
| real environment variable, but command substitution does not run inside a
| YAML env map, so the value is an invalid literal string. A real environment
| variable takes precedence over phpunit.xml's <env> entries, so we normalise
| it here (overriding putenv/$_ENV/$_SERVER) to keep the suite self-contained.
|
*/

$appKey = 'base64:2yqEKgw1kWZ3KpQ0T0JKwhNkTitbP+6monDOO8SQwXk=';

if (! str_starts_with((string) getenv('APP_KEY'), 'base64:') || strlen((string) getenv('APP_KEY')) !== strlen($appKey)) {
    putenv("APP_KEY={$appKey}");
    $_ENV['APP_KEY'] = $appKey;
    $_SERVER['APP_KEY'] = $appKey;
}

require __DIR__.'/../vendor/autoload.php';

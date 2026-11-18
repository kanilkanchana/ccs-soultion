<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$laravelPath = '/home/u188760634/domains/ccssolutions.lk/ccs-admin-app';

if (file_exists($maintenance = $laravelPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelPath.'/vendor/autoload.php';

$app = require_once $laravelPath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
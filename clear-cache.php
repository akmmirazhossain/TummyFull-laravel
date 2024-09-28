<?php

// Include the Laravel bootstrap file
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Artisan;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::createFromGlobals()
);

// Clear the cache
Artisan::call('cache:clear');
Artisan::call('config:cache');
Artisan::call('route:cache');
Artisan::call('view:clear');
Artisan::call('optimize:clear');

echo "Cache cleared successfully!";

<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    \App\Providers\CcsWebsiteServiceProvider::class,
    \App\Providers\CcsDatabaseViewerServiceProvider::class,
];

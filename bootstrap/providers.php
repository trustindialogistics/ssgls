<?php

use App\Hashids\HashidsServiceProvider;
use App\Providers\AppConfigProvider;
use App\Providers\AppServiceProvider;
use App\Providers\DropboxServiceProvider;
use App\Providers\PDFServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\ViewServiceProvider;
use App\Providers\EventServiceProvider;

return [
    HashidsServiceProvider::class,
    AppServiceProvider::class,
    EventServiceProvider::class,
    RouteServiceProvider::class,
    DropboxServiceProvider::class,
    ViewServiceProvider::class,
    PDFServiceProvider::class,
    AppConfigProvider::class,
];


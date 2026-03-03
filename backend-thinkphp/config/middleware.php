<?php
return [
    \app\middleware\Cors::class,
    \app\middleware\LogMiddleware::class,
    \app\middleware\Tenant::class,
];

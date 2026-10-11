<?php

use Illuminate\Support\Facades\Route;

$serveFrontend = function () {
    $indexPath = public_path('frontend/index.html');
    abort_unless(
        is_file($indexPath),
        503,
        'Frontend build not found. Build the frontend and upload it to backend/public/frontend.'
    );

    return response()->file($indexPath);
};

Route::get('/', $serveFrontend);
Route::get('/{path}', $serveFrontend)
    ->where('path', '^(?!api(?:/|$)|frontend(?:/|$)).*');

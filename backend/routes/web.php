<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $indexPath = public_path('frontend/index.html');

    return is_file($indexPath) ? response()->file($indexPath) : view('welcome');
});

Route::get('/{path}', function () {
    $indexPath = public_path('frontend/index.html');
    abort_unless(is_file($indexPath), 404);

    return response()->file($indexPath);
})->where('path', '^(?!api(?:/|$)|frontend(?:/|$)).*');

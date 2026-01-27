<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/version', function () {
    return config('app.name') . ' version ' . config('app.version');
});

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Translation Management Service',
        'version' => '1.0.0',
        'status' => 'active',
    ]);
});

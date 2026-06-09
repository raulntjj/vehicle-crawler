<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'active',
        'service' => 'vehicle-crawler-etl'
    ]);
});

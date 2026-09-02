<?php

use App\Http\Controllers\ApplicationController;
use Illuminate\Support\Facades\Route;

Route::middleware('apptracker.token')->group(function () {
    Route::apiResource('applications', ApplicationController::class);
});

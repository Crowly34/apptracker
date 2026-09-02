<?php

use App\NativeComponents\ApplicationDetail;
use App\NativeComponents\ApplicationsList;
use App\NativeComponents\Settings;
use Illuminate\Support\Facades\Route;

Route::native('/', ApplicationsList::class);
Route::native('/applications/{id}', ApplicationDetail::class);
Route::native('/settings', Settings::class);

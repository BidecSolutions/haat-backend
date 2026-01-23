<?php

use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('analytics')->group(function(){
    Route::get('/overview', [UserController::class, 'overview']);
    Route::get('/users', [UserController::class, 'users']);
    Route::get('marketplace', [ListingController::class, 'marketplaceListing']);
});
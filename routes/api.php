<?php

use Illuminate\Support\Facades\Route;
use \Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\Api\{
    AdminAuthController,
    UserAuthController,
    UserController,
    CategoryController,
};

    Route::get('/clear', function(){
        Artisan::call('optimize:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
    });

    // For Users
    include('modules/website.php');

    // For Users
    include('modules/user.php');

    // For Admins
    include('modules/admin.php');

    // For Tableau
    include('modules/analytics.php');

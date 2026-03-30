<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\Api\{
    AdminAuthController,
    AdminController,
    UserController,
    CategoryController,
    ListingController,
    BidController,
    ListingReportController,
    WatchlistController,
    AdminAnalyticsController,
    ChatbotController,
    CityController,
    CountryController,
    EmailTestController,
    CodeController,
    ContactMessageController,
    DashboardController,
    GovernorateController,
    InstructionController,
    ListingAttributeController,
    ModuleController,
    PromotionController,
    RegionController,
};
use App\Http\Controllers\AreaController;
use App\Http\Controllers\BlogsController;
use App\Http\Controllers\FeedbackResponseController;

// Admin Auth
Route::prefix('admin')->controller(AdminAuthController::class)->group(function () {
    Route::post('/login', 'login'); // Public login
});

//Admin Guarded Routes
Route::middleware('auth:admin-api')->prefix('admin')->group(function () {

    Route::controller(AdminAuthController::class)->group(function () {
        Route::get('/profile', 'profile');
        Route::post('/logout', 'logout');
        Route::post('/change-password', 'changePassword');
        Route::post('/{id}/update', 'update');
    });

    // Admin Controller works
    Route::controller(AdminController::class)->group(function () {
        Route::get('/list', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}/changeactiveInactive', 'changeactiveInactive');
    });

    Route::controller(FeedbackResponseController::class)->group(function (){
        Route::get('feedback', 'index');
    });

    // UserController works
    Route::prefix('user')->controller(UserController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/edit', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}/changeactiveInactive', 'changeactiveInactive');
        Route::get('/restoreUser/{id}', 'restoreUser');
    });

    // RolePermissionCOntroller works
    Route::controller(RolePermissionController::class)->group(function () {
        Route::get('/roles', 'listRoles');
        Route::post('/create-role', 'createRole');
        Route::post('/update-role/{id}', 'updateRole');

        Route::get('/permissions', 'listPermissionsOnDemand');
        Route::get('/permissions/list', 'listPermissions');
        Route::post('/create-permission', 'createPermission');
        Route::post('/update-permission/{id}', 'updatePermission');
        Route::delete('permissions/destroy/{id}', 'destroy');

        Route::get('/list-admin-roles', 'listAdminRoles');
        Route::post('/assign-role-admin', 'assignRoleToAdmin');
        Route::post('/assign-permission-admin', 'assignPermissionToRole');
        Route::post('/assign-role-user', 'assignRoleToUser');
        Route::post('/assign-permission-user', 'assignPermissionToUser');
    });

    // CategoryController works
    Route::prefix('category')->controller(CategoryController::class)->group(function () {
        Route::get('/', 'adminIndex');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::patch('/{id}/toggle', 'toggleStatus');
        Route::delete('/{id}/destroy', 'destroy');
        Route::delete('/{id}/deleteSubcategories', 'deleteSubcategories');
    });

    Route::prefix('blogs')->controller(BlogsController::class)->group(function () {
        Route::get('/index', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}', 'destroy');
    });
    Route::prefix('promotions')->controller(PromotionController::class)->group(function () {
        Route::get('/index', 'list');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}', 'destroy');
    });
    Route::prefix('instructions')->controller(InstructionController::class)->group(function () {
        Route::get('/index', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}', 'destroy');
    });
    Route::prefix('regions')->controller(RegionController::class)->group(function () {
        Route::get('/index', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}', 'destroy');
        // Route::apiResource('/', RegionController::class)->parameters(['' => 'region']);
    });
    Route::prefix('governorate')->controller(GovernorateController::class)->group(function () {
        Route::get('/index', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}', 'destroy');
        // Route::apiResource('/', GovernorateController::class)->parameters(['' => 'governorate']);
    });
    Route::prefix('countries')->controller(CountryController::class)->group(function () {
        Route::get('/index', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}', 'destroy');
        // Route::apiResource('/', GovernorateController::class)->parameters(['' => 'governorate']);
    });
    Route::prefix('cities')->controller(CityController::class)->group(function () {
        Route::get('/index', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}', 'destroy');
        // Route::apiResource('/', GovernorateController::class)->parameters(['' => 'governorate']);
    });
    Route::prefix('areas')->controller(AreaController::class)->group(function () {
        Route::get('/index', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}', 'destroy');
    });

    // Route::apiResource('countries', CountryController::class);
    // Route::apiResource('cities', CityController::class);
     // ListingController works
    Route::prefix('listings')->controller(ListingController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/views', 'views');
        Route::get('/{slug}/show', 'show');
        Route::post('/store', 'adminStore');
        Route::post('/{slug}/update', 'adminUpdate');
        Route::delete('/{slug}/destroy', 'destroy');
        Route::put('/{slug}/approve', 'approve');
        Route::put('/{slug}/reject', 'reject');
        Route::get('/type/{type}', 'indexByType');
        Route::patch('/{slug}/toggle', 'toggleStatus');
        Route::patch('/{slug}/toggle-featured', 'toggleFeatured');
    });

    Route::prefix('listings')->controller(BidController::class)->group(function () {
        Route::get('{listingID}/bids', 'index');
    });

    Route::prefix('listings')->controller(ListingReportController::class)->group(function () {
        Route::get('/reports', 'index');
    });

    Route::prefix('analytics')->controller(AdminAnalyticsController::class)->group(function () {
        Route::get('/top-bidders', 'topBidders');
        Route::get('/hot-listings', 'hotListings');
    });
    
    // ContactMessageController works
    Route::prefix('contact')->controller(ContactMessageController::class)->group(function () {
        Route::get('messages', 'index');
    });

    // Email Testing and Configuration
    Route::prefix('email')->controller(EmailTestController::class)->group(function () {
        Route::get('/config', 'getEmailConfig');
        Route::post('/test', 'testEmail');
        Route::post('/welcome', 'sendWelcomeEmail');
        Route::post('/ending-soon', 'sendEndingSoonNotifications');
        Route::post('/test-all-notifications', 'testAllNotifications');
    });

    // CodeController Routes
    Route::prefix('codes')->controller(CodeController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}/delete', 'destroy');
        Route::post('/{id}/changeactiveInactive', 'toggleStatus');
    });

    //ListingAttributesController Routes
    Route::prefix('listing-attributes')->controller(ListingAttributeController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}/delete', 'destroy');
    });

    // Module Management Routes
    Route::prefix('modules')->controller(ModuleController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/store', 'store');
        Route::get('/{id}/show', 'show');
        Route::post('/{id}/update', 'update');
        Route::patch('/{id}/toggle', 'toggleStatus');
        Route::delete('/{id}', 'destroy');
    });

    // Chatbot FAQ Management
    Route::prefix('chatbot')->controller(ChatbotController::class)->group(function () {
        Route::get('/', 'adminIndex');
        Route::post('/store', 'store');
        Route::post('/{id}/update', 'update');
        Route::delete('/{id}', 'destroy');
    });

    // Dashboard Stats
    Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {
        Route::get('/stats', 'stats');
    });
});


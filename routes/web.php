<?php

use Database\Seeders\DeleteSubcategory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('migrate', function () {
    // Run migrations
    Artisan::call('migrate');
    $migrateOutput = Artisan::output();

    // Get migration status
    Artisan::call('migrate:status');
    $statusOutput = Artisan::output();

    // Output both
    echo '<pre>';
    echo "Migration Output:\n".$migrateOutput;
    echo "\n\nMigration Status:\n".$statusOutput;
    echo "\n\nAll migrations ran successfully.";
    echo '</pre>';
});

Route::get('migrate-rollback', function () {
    // Run rollback
    Artisan::call('migrate:rollback');
    $rollbackOutput = Artisan::output();

    // Get migration status
    Artisan::call('migrate:status');
    $statusOutput = Artisan::output();

    // Output both
    echo '<pre>';
    echo "Rollback Output:\n".$rollbackOutput;
    echo "\n\nMigration Status:\n".$statusOutput;
    echo "\n\nRollback executed successfully.";
    echo '</pre>';
});

Route::get('rollback', function () {
    // Run migrations
    Artisan::call('migrate:rollback');
    $migrateOutput = Artisan::output();

    // Get migration status
    Artisan::call('migrate:status');
    $statusOutput = Artisan::output();

    // Output both
    echo '<pre>';
    echo "Migration Rollback Output:\n".$migrateOutput;
    echo "\n\nMigration Status:\n".$statusOutput;
    echo "\n\nAll migrations ran successfully.";
    echo '</pre>';
});

Route::get('/run-app-schedule', function () {
    // Run first command and capture output
    Artisan::call('app:close-expired-listings');
    $output1 = Artisan::output();

    // Run second command and capture output
    Artisan::call('offers:expire');
    $output2 = Artisan::output();

    // Combine both outputs with headers and line breaks
    $finalOutput = "✅ app:close-expired-listings Output:\n".$output1;
    $finalOutput .= "\n\n✅ offers:expire Output:\n".$output2;

    return nl2br($finalOutput);
});

Route::get('clear', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    echo 'All optimizations clear successfully';
});
Route::get('/run-app-schedule', function () {
    // Run first command and capture output
    Artisan::call('app:close-expired-listings');
    $output1 = Artisan::output();

    // Run second command and capture output
    Artisan::call('offers:expire');
    $output2 = Artisan::output();

    // Combine both outputs with headers and line breaks
    $finalOutput = "✅ app:close-expired-listings Output:\n".$output1;
    $finalOutput .= "\n\n✅ offers:expire Output:\n".$output2;

    return nl2br($finalOutput);
});

Route::get('seed-locations', function () {
    // dd('here');
    Artisan::call('db:seed', [
        // '--class' => 'PaymentMethodSeeder',
        '--force' => true, // Allows running in production
    ]);

    $output = Artisan::output();

    echo '<pre>';
    echo "Seeding Output:\n".$output;
    echo "\n\nLocations have been seeded successfully.";
    echo '</pre>';
});

Route::get('seed-categories', function () {
    // dd('here');
    try {
        Artisan::call('db:seed', [
            '--class' => 'CategorySeeder',
            '--force' => true,
        ]);

        $output = Artisan::output();

        return response()->json([
            'status' => true,
            'message' => 'Seeder executed successfully.',
            'output' => $output,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Seeder failed to execute.',
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('seed-username', function () {
    // dd('here');
    try {
        Artisan::call('db:seed', [
            '--class' => 'UpdateUsernamesSeeder',
            '--force' => true,
        ]);

        $output = Artisan::output();

        return response()->json([
            'status' => true,
            'message' => 'Seeder of user name executed successfully.',
            'output' => $output,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Seeder of user name failed to execute.',
            'error' => $e->getMessage(),
        ], 500);
    }
});
Route::get('seed-memberId', function () {
    // dd('here');
    try {
        Artisan::call('db:seed', [
            '--class' => 'AddMemberId',
            '--force' => true,
        ]);

        $output = Artisan::output();

        return response()->json([
            'status' => true,
            'message' => 'Seeder of member id executed successfully.',
            'output' => $output,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Seeder of Member id failed to execute.',
            'error' => $e->getMessage(),
        ], 500);
    }
});
Route::get('seed-admin', function () {
    // dd('here');
    try {
        Artisan::call('db:seed', [
            '--class' => 'AdminSeeder',
            '--force' => true,
        ]);

        $output = Artisan::output();

        return response()->json([
            'status' => true,
            'message' => 'Seeder of user name executed successfully.',
            'output' => $output,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Seeder of user name failed to execute.',
            'error' => $e->getMessage(),
        ], 500);
    }
});
Route::get('seed-vehicle-makes', function () {
    Artisan::call('db:seed', ['--class' => 'VehicleMakeSeeder', '--force' => true]);

    return [
        'status' => true,
        'message' => 'Makes seeded.',
        'total' => DB::table('vehicle_data')->whereNull('model')->count(),
    ];
});

Route::get('seed-vehicle-models', function () {
    Artisan::call('db:seed', ['--class' => 'VehicleModelSeeder', '--force' => true]);

    return [
        'status' => true,
        'message' => 'Models seeded.',
        'total' => DB::table('vehicle_data')->whereNotNull('model')->whereNull('year')->count(),
    ];
});

Route::get('seed-vehicle-years', function () {
    Artisan::call('db:seed', [
            '--class' => 'VehicleYearSeeder',
            '--force' => true,
        ]);
    return [
        'status' => true,
        'message' => 'vehicle seeder successfully',
    ];
});
// 🚗 Seed Promotions
Route::get('seed-promotions', function () {
    Artisan::call('db:seed', [
        '--class' => 'PromotionSeeder',
        '--force' => true,
    ]);

    return [
        'status' => true,
        'message' => 'Promotions seeded.',
        'total' => DB::table('promotions')->count(),
    ];
});
Route::get('delete-subcategory/{id}', function ($id) {
    $seeder = new DeleteSubcategory;
    $seeder->run($id);

    return [
        'status' => true,
        'message' => 'DeleteSubcategory with its lsitgin are successfully delted.',
    ];
});

// 📘 Seed Instructions
Route::get('seed-instructions', function () {
    Artisan::call('db:seed', [
        '--class' => 'InstructionSeeder',
        '--force' => true,
    ]);

    return [
        'status' => true,
        'message' => 'Instructions seeded.',
        'total' => DB::table('instructions')->count(),
    ];
});

Route::get('/seed-skills', function () {
    Artisan::call('db:seed', [
        '--class' => 'SkillsSeeder',
        '--force' => true,
    ]);

    return [
        'status' => true,
        'message' => 'Skills seeded.',
    ];
});

Route::get('/seed-regions', function () {
    Artisan::call('db:seed', [
        '--class' => 'RegionsSeeder',
        '--force' => true,
    ]);

    return [
        'status' => true,
        'message' => 'Regions seeded.',
        'totalRegions' => DB::table('regions')->count(),
        'totalGovernorates' => DB::table('governorates')->count(),
    ];
});
Route::get('/seed-feedback', function () {
    Artisan::call('db:seed', [
        '--class' => 'FeedbackSeeder',
        '--force' => true,
    ]);

    return [
        'status' => true,
        'message' => 'feedback seeded.',
        'tottal_feed_back_questions' => DB::table('feedback_questions')->count(),
        'tottal_answers' => DB::table('feedback_question_options')->count(),
    ];
});
Route::get('/delete-categories-listings', function () {
    Artisan::call('db:seed', [
        '--class' => 'ForRemovingSomeCategories',
        '--force' => true,
    ]);

    return [
        'status' => true,
        'message' => 'category of motors that are child deleted.',
        'totalCategories' => DB::table('category')->count(),
    ];
});

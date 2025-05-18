<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Admin\AuthController;
use App\Http\Controllers\V1\Admin\SessionController;
use App\Http\Controllers\V1\Admin\UserController;
use App\Http\Controllers\V1\Admin\ActivityLogController;
use App\Http\Controllers\V1\Admin\BrandController;
use App\Http\Controllers\V1\Admin\CategoryController;
use App\Http\Controllers\V1\Admin\ProfileController;
use App\Http\Controllers\V1\Admin\SettingsController;

use App\Http\Controllers\V1\Admin\ColorSeasonController;
use App\Http\Controllers\V1\Admin\ColorController;
use App\Http\Controllers\V1\Admin\WarehouseController;
use App\Http\Controllers\V1\Admin\ShelfController;
use App\Http\Controllers\V1\Admin\SizeController;
use App\Http\Controllers\V1\Admin\TagController;
use App\Http\Controllers\V1\Admin\ConfigurationController;
use App\Http\Controllers\V1\Admin\HomeSectionController;
use App\Http\Controllers\V1\Admin\LearningVideoController;
use App\Http\Controllers\V1\Admin\OccupationController;
use App\Http\Controllers\V1\Admin\ProductController;
use App\Http\Controllers\V1\Admin\ProductImageController;
use App\Http\Controllers\V1\Admin\ProductVariantController;

Route::prefix('v1')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::middleware(['guest', 'recaptcha'])->group(function () {
            Route::post('login', [AuthController::class, 'login']);
            Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
            Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
            Route::post('reset-password', [AuthController::class, 'resetPassword']);
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::middleware('auth.actions')->group(function () {
                Route::get('getCurrentUser', [ProfileController::class, 'getCurrentUser']);
                Route::post('logout', [ProfileController::class, 'logout']);
                Route::get('allSettings', [SettingsController::class, 'index']);
                Route::middleware('can:view-activity-logs')->get('activity-logs', [ActivityLogController::class, 'index']);

                Route::middleware('can:view-profile')->post('changePassword', [ProfileController::class, 'changePassword']);
                Route::middleware('can:view-profile')->get('sessions', [SessionController::class, 'getAllSessions']);
                Route::middleware('can:view-profile')->post('logoutOtherDevices', [SessionController::class, 'logoutOtherDevices']);
                Route::middleware('can:view-profile')->post('logoutSpecificDevice', [SessionController::class, 'logoutSpecificDevice']);

                Route::middleware('can:view-user')->get('users', [UserController::class, 'index']);
                Route::middleware('can:view-user')->get('users/{id}', [UserController::class, 'show']);
                Route::middleware('can:create-user')->post('users', [UserController::class, 'create']);
                Route::middleware('can:edit-user')->put('users/{user}', [UserController::class, 'update']);
                Route::middleware('can:delete-user')->delete('users/{user}', [UserController::class, 'destroy']);

                Route::middleware('can:view-category')->get('categories', [CategoryController::class, 'index']);
                Route::middleware('can:view-category')->get('categories/{category}', [CategoryController::class, 'show']);
                Route::middleware('can:create-category')->post('categories', [CategoryController::class, 'store']);
                Route::middleware('can:edit-category')->put('categories/{category}', [CategoryController::class, 'update']);
                Route::middleware('can:delete-category')->delete('categories/{category}', [CategoryController::class, 'destroy']);

                // // Brand Routes
                // Route::middleware('can:view-brand')->get('brands', [BrandController::class, 'index']);
                // Route::middleware('can:view-brand')->get('brands/{brand}', [BrandController::class, 'show']);
                // Route::middleware('can:create-brand')->post('brands', [BrandController::class, 'store']);
                // Route::middleware('can:edit-brand')->put('brands/{brand}', [BrandController::class, 'update']);
                // Route::middleware('can:delete-brand')->delete('brands/{brand}', [BrandController::class, 'destroy']);

                // // ColorSeason Routes
                // Route::middleware('can:view-color-season')->get('color-seasons', [ColorSeasonController::class, 'index']);
                // Route::middleware('can:view-color-season')->get('color-seasons/{colorSeason}', [ColorSeasonController::class, 'show']);
                // Route::middleware('can:create-color-season')->post('color-seasons', [ColorSeasonController::class, 'store']);
                // Route::middleware('can:edit-color-season')->put('color-seasons/{colorSeason}', [ColorSeasonController::class, 'update']);
                // Route::middleware('can:delete-color-season')->delete('color-seasons/{colorSeason}', [ColorSeasonController::class, 'destroy']);

                // // Color Routes
                // Route::middleware('can:view-color')->get('colors', [ColorController::class, 'index']);
                // Route::middleware('can:view-color')->get('colors/{color}', [ColorController::class, 'show']);
                // Route::middleware('can:create-color')->post('colors', [ColorController::class, 'store']);
                // Route::middleware('can:edit-color')->put('colors/{color}', [ColorController::class, 'update']);
                // Route::middleware('can:delete-color')->delete('colors/{color}', [ColorController::class, 'destroy']);

                // // Warehouse Routes
                // Route::middleware('can:view-warehouse')->get('warehouses', [WarehouseController::class, 'index']);
                // Route::middleware('can:view-warehouse')->get('warehouses/{warehouse}', [WarehouseController::class, 'show']);
                // Route::middleware('can:create-warehouse')->post('warehouses', [WarehouseController::class, 'store']);
                // Route::middleware('can:edit-warehouse')->put('warehouses/{warehouse}', [WarehouseController::class, 'update']);
                // Route::middleware('can:delete-warehouse')->delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy']);

                // // Shelf Routes
                // Route::middleware('can:view-shelf')->get('shelves', [ShelfController::class, 'index']);
                // Route::middleware('can:view-shelf')->get('shelves/{shelf}', [ShelfController::class, 'show']);
                // Route::middleware('can:create-shelf')->post('shelves', [ShelfController::class, 'store']);
                // Route::middleware('can:edit-shelf')->put('shelves/{shelf}', [ShelfController::class, 'update']);
                // Route::middleware('can:delete-shelf')->delete('shelves/{shelf}', [ShelfController::class, 'destroy']);

                // // Size Routes
                // Route::middleware('can:view-size')->get('sizes', [SizeController::class, 'index']);
                // Route::middleware('can:view-size')->get('sizes/{size}', [SizeController::class, 'show']);
                // Route::middleware('can:create-size')->post('sizes', [SizeController::class, 'store']);
                // Route::middleware('can:edit-size')->put('sizes/{size}', [SizeController::class, 'update']);
                // Route::middleware('can:delete-size')->delete('sizes/{size}', [SizeController::class, 'destroy']);

                // // Tag Routes
                // Route::middleware('can:view-tag')->get('tags', [TagController::class, 'index']);
                // Route::middleware('can:view-tag')->get('tags/{tag}', [TagController::class, 'show']);
                // Route::middleware('can:create-tag')->post('tags', [TagController::class, 'store']);
                // Route::middleware('can:edit-tag')->put('tags/{tag}', [TagController::class, 'update']);
                // Route::middleware('can:delete-tag')->delete('tags/{tag}', [TagController::class, 'destroy']);

                // // Configuration Routes
                // Route::middleware('can:view-configuration')->get('configurations', [ConfigurationController::class, 'index']);
                // Route::middleware('can:edit-configuration')->put('configurations', [ConfigurationController::class, 'update']);

                // // Learning Video Routes
                // Route::middleware('can:view-learning-video')->get('learning-videos', [LearningVideoController::class, 'index']);
                // Route::middleware('can:view-learning-video')->get('learning-videos/{learning_video}', [LearningVideoController::class, 'show']);
                // Route::middleware('can:create-learning-video')->post('learning-videos', [LearningVideoController::class, 'store']);
                // Route::middleware('can:edit-learning-video')->put('learning-videos/{learning_video}', [LearningVideoController::class, 'update']);
                // Route::middleware('can:delete-learning-video')->delete('learning-videos/{learning_video}', [LearningVideoController::class, 'destroy']);

                // // Home Section Routes
                // Route::middleware('can:view-home-section')->get('home-sections', [HomeSectionController::class, 'index']);
                // Route::middleware('can:view-home-section')->get('home-sections/{home_section}', [HomeSectionController::class, 'show']);
                // Route::middleware('can:create-home-section')->post('home-sections', [HomeSectionController::class, 'store']);
                // Route::middleware('can:edit-home-section')->put('home-sections/{home_section}', [HomeSectionController::class, 'update']);
                // Route::middleware('can:delete-home-section')->delete('home-sections/{home_section}', [HomeSectionController::class, 'destroy']);

                // //Occupations Routes
                // Route::middleware('can:view-occupation')->get('occupations', [OccupationController::class, 'index']);
                // Route::middleware('can:view-occupation')->get('occupations/{occupation}', [OccupationController::class, 'show']);
                // Route::middleware('can:create-occupation')->post('occupations', [OccupationController::class, 'store']);
                // Route::middleware('can:edit-occupation')->put('occupations/{occupation}', [OccupationController::class, 'update']);
                // Route::middleware('can:delete-occupation')->delete('occupations/{occupation}', [OccupationController::class, 'destroy']);

                //product Routes
                Route::middleware('can:view-product')->get('products', [ProductController::class, 'index']);
                Route::middleware('can:view-product')->get('products/{product}', [ProductController::class, 'show']);
                Route::middleware('can:create-product')->post('products', [ProductController::class, 'store']);
                Route::middleware('can:edit-product')->put('products/{product}', [ProductController::class, 'update']);
                Route::middleware('can:delete-product')->delete('products/{product}', [ProductController::class, 'destroy']);
                Route::middleware('can:edit-product')->group(function () {
                    Route::put('product_image/{product_image}', [ProductImageController::class, 'update']);
                    Route::delete('product_image/{product_image}', [ProductImageController::class, 'destroy']);
                    Route::delete('product_variant/{product_variant}', [ProductVariantController::class, 'destroy']);
                });


                Route::middleware('can:view-settings')->group(function () {
                    // Brand Routes
                    Route::get('brands', [BrandController::class, 'index']);
                    Route::get('brands/{brand}', [BrandController::class, 'show']);
                    Route::post('brands', [BrandController::class, 'store']);
                    Route::put('brands/{brand}', [BrandController::class, 'update']);
                    Route::delete('brands/{brand}', [BrandController::class, 'destroy']);

                    // ColorSeason Routes
                    Route::get('color-seasons', [ColorSeasonController::class, 'index']);
                    Route::get('color-seasons/{colorSeason}', [ColorSeasonController::class, 'show']);
                    Route::post('color-seasons', [ColorSeasonController::class, 'store']);
                    Route::put('color-seasons/{colorSeason}', [ColorSeasonController::class, 'update']);
                    Route::delete('color-seasons/{colorSeason}', [ColorSeasonController::class, 'destroy']);

                    // Color Routes
                    Route::get('colors', [ColorController::class, 'index']);
                    Route::get('colors/{color}', [ColorController::class, 'show']);
                    Route::post('colors', [ColorController::class, 'store']);
                    Route::put('colors/{color}', [ColorController::class, 'update']);
                    Route::delete('colors/{color}', [ColorController::class, 'destroy']);

                    // Warehouse Routes
                    Route::get('warehouses', [WarehouseController::class, 'index']);
                    Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show']);
                    Route::post('warehouses', [WarehouseController::class, 'store']);
                    Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update']);
                    Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy']);

                    // Shelf Routes
                    Route::get('shelves', [ShelfController::class, 'index']);
                    Route::get('shelves/{shelf}', [ShelfController::class, 'show']);
                    Route::post('shelves', [ShelfController::class, 'store']);
                    Route::put('shelves/{shelf}', [ShelfController::class, 'update']);
                    Route::delete('shelves/{shelf}', [ShelfController::class, 'destroy']);

                    // Size Routes
                    Route::get('sizes', [SizeController::class, 'index']);
                    Route::get('sizes/{size}', [SizeController::class, 'show']);
                    Route::post('sizes', [SizeController::class, 'store']);
                    Route::put('sizes/{size}', [SizeController::class, 'update']);
                    Route::delete('sizes/{size}', [SizeController::class, 'destroy']);

                    // Tag Routes
                    Route::get('tags', [TagController::class, 'index']);
                    Route::get('tags/{tag}', [TagController::class, 'show']);
                    Route::post('tags', [TagController::class, 'store']);
                    Route::put('tags/{tag}', [TagController::class, 'update']);
                    Route::delete('tags/{tag}', [TagController::class, 'destroy']);

                    // Configuration Routes
                    Route::get('configurations', [ConfigurationController::class, 'index']);
                    Route::put('configurations', [ConfigurationController::class, 'update']);

                    // Learning Video Routes
                    Route::get('learning-videos', [LearningVideoController::class, 'index']);
                    Route::get('learning-videos/{learning_video}', [LearningVideoController::class, 'show']);
                    Route::post('learning-videos', [LearningVideoController::class, 'store']);
                    Route::put('learning-videos/{learning_video}', [LearningVideoController::class, 'update']);
                    Route::delete('learning-videos/{learning_video}', [LearningVideoController::class, 'destroy']);

                    // Home Section Routes
                    Route::get('home-sections', [HomeSectionController::class, 'index']);
                    Route::get('home-sections/{home_section}', [HomeSectionController::class, 'show']);
                    Route::post('home-sections', [HomeSectionController::class, 'store']);
                    Route::put('home-sections/{home_section}', [HomeSectionController::class, 'update']);
                    Route::delete('home-sections/{home_section}', [HomeSectionController::class, 'destroy']);

                    // Occupation Routes
                    Route::get('occupations', [OccupationController::class, 'index']);
                    Route::get('occupations/{occupation}', [OccupationController::class, 'show']);
                    Route::post('occupations', [OccupationController::class, 'store']);
                    Route::put('occupations/{occupation}', [OccupationController::class, 'update']);
                    Route::delete('occupations/{occupation}', [OccupationController::class, 'destroy']);
                });
            });
        });
    });
});

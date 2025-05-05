<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Admin\AuthController;
use App\Http\Controllers\V1\Admin\SessionController;
use App\Http\Controllers\V1\Admin\UserController;
use App\Http\Controllers\V1\Admin\ActivityLogController;
use App\Http\Controllers\V1\Admin\CategoryController;
use App\Http\Controllers\V1\Admin\ProfileController;
use App\Http\Controllers\V1\Admin\SettingsController;

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
            });
        });
    });
});

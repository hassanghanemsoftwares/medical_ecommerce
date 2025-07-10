<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Admin\AuthController;
use App\Http\Controllers\V1\Admin\SessionController;
use App\Http\Controllers\V1\Admin\UserController;
use App\Http\Controllers\V1\Admin\ActivityLogController;
use App\Http\Controllers\V1\Admin\AddressController;
use App\Http\Controllers\V1\Admin\BrandController;
use App\Http\Controllers\V1\Admin\CategoryController;
use App\Http\Controllers\V1\Admin\ClientController;
use App\Http\Controllers\V1\Admin\ProfileController;
use App\Http\Controllers\V1\Admin\SettingsController;
use App\Http\Controllers\V1\Admin\ColorSeasonController;
use App\Http\Controllers\V1\Admin\ColorController;
use App\Http\Controllers\V1\Admin\WarehouseController;
use App\Http\Controllers\V1\Admin\ShelfController;
use App\Http\Controllers\V1\Admin\SizeController;
use App\Http\Controllers\V1\Admin\TagController;
use App\Http\Controllers\V1\Admin\ConfigurationController;
use App\Http\Controllers\V1\Admin\ContactController;
use App\Http\Controllers\V1\Admin\CouponController;
use App\Http\Controllers\V1\Admin\HomeBannerController;
use App\Http\Controllers\V1\Admin\HomeProductSectionItemController;
use App\Http\Controllers\V1\Admin\HomeSectionController;
use App\Http\Controllers\V1\Admin\LearningVideoController;
use App\Http\Controllers\V1\Admin\OccupationController;
use App\Http\Controllers\V1\Admin\OrderController;
use App\Http\Controllers\V1\Admin\PreOrderController;
use App\Http\Controllers\V1\Admin\ProductController;
use App\Http\Controllers\V1\Admin\ProductImageController;
use App\Http\Controllers\V1\Admin\ProductVariantController;
use App\Http\Controllers\V1\Admin\ReturnOrderController;
use App\Http\Controllers\V1\Admin\StockAdjustmentController;
use App\Http\Controllers\V1\Admin\StockController;
use App\Http\Controllers\V1\Admin\SubscriptionController;
use App\Http\Controllers\V1\Admin\SubscriptionPlanController;
use App\Http\Controllers\V1\Admin\TeamMemberController;
use App\Http\Controllers\V1\Client\ClientAddressController;
use App\Http\Controllers\V1\Client\ClientAuthController;
use App\Http\Controllers\V1\Client\ClientCartController;
use App\Http\Controllers\V1\Client\ClientCheckoutController;
use App\Http\Controllers\V1\Client\ClientContactController;
use App\Http\Controllers\V1\Client\ClientCouponController;
use App\Http\Controllers\V1\Client\ClientHomeController;
use App\Http\Controllers\V1\Client\ClientLearningVideoController;
use App\Http\Controllers\V1\Client\ClientNewsletterController;
use App\Http\Controllers\V1\Client\ClientOrdersController;
use App\Http\Controllers\V1\Client\ClientPreOrdersController;
use App\Http\Controllers\V1\Client\ClientProductController;
use App\Http\Controllers\V1\Client\ClientProfileController;
use App\Http\Controllers\V1\Client\ClientReturnOrdersController;
use App\Http\Controllers\V1\Client\ClientSettingsController;
use App\Http\Controllers\V1\Client\ClientShopController;
use App\Http\Controllers\V1\Client\ClientSubscriptionPlanController;
use App\Http\Controllers\V1\Client\ClientTeamMemberController;
use App\Http\Controllers\V1\Client\ClientWishlistController;

Route::prefix('v1')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::middleware(['manage_auth_session', 'app_auth'])->group(function () {
            Route::get('/app-launch', function () {
                return response()->json([
                    'result' => true,
                    'message' => 'App launch successful and session managed.',
                ]);
            });
            Route::middleware(['guest', 'recaptcha'])->group(function () {
                Route::post('login', [AuthController::class, 'login']);
                Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
                Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
                Route::post('reset-password', [AuthController::class, 'resetPassword']);
            });

            Route::middleware(['auth:sanctum'])->group(function () {
                Route::middleware('auth.actions')->group(function () {
                    Route::get('getCurrentUser', [ProfileController::class, 'getCurrentUser']);
                    Route::post('logout', [ProfileController::class, 'logout']);
                    Route::get('allSettings', [SettingsController::class, 'index']);
                    Route::get('getNotifications', [SettingsController::class, 'getNotifications']);

                    Route::middleware('can:view-activity_logs')->get('activity-logs', [ActivityLogController::class, 'index']);


                    Route::get('getAllClients', [SettingsController::class, 'getAllClients']);
                    Route::get('getAllProductsVariants', [SettingsController::class, 'getAllProductsVariants']);
                    Route::get('getOrderableVariants', [SettingsController::class, 'getOrderableVariants']);
                    Route::get('getClientAddresses', [SettingsController::class, 'getClientAddresses']);
                    Route::get('getOrdersCanBeReturned', [SettingsController::class, 'getOrdersCanBeReturned']);
                    Route::get('getAllProductsVariantsCanBePreOrder', [SettingsController::class, 'getAllProductsVariantsCanBePreOrder']);
                    Route::get('getAllProducts', [SettingsController::class, 'getAllProducts']);


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

                    //product Routes
                    Route::middleware('can:edit-product')->get('products/generate-barcode', [ProductController::class, 'generateBarcode']);
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
                    Route::get('/stocks', [StockController::class, 'index'])
                        ->middleware('can:view-stock');

                    // StockAdjustment routes
                    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])
                        ->middleware('can:view-stock_adjustment');

                    Route::get('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])
                        ->middleware('can:view-stock_adjustment');

                    Route::post('/stock-adjustments/manual', [StockAdjustmentController::class, 'manualAdjustWithDirection'])
                        ->middleware('can:create-stock_adjustment');

                    Route::middleware('can:view-client')->get('clients', [ClientController::class, 'index']);
                    Route::middleware('can:view-client')->get('clients/{client}', [ClientController::class, 'show']);
                    Route::middleware('can:create-client')->post('clients', [ClientController::class, 'store']);
                    Route::middleware('can:edit-client')->put('clients/{client}', [ClientController::class, 'update']);
                    Route::middleware('can:delete-client')->delete('clients/{client}', [ClientController::class, 'destroy']);

                    Route::middleware('can:view-client')->get('addresses', [AddressController::class, 'index']);
                    Route::middleware('can:view-client')->get('addresses/{address}', [AddressController::class, 'show']);
                    Route::middleware('can:view-client')->post('addresses', [AddressController::class, 'store']);
                    Route::middleware('can:view-client')->put('addresses/{address}', [AddressController::class, 'update']);
                    Route::middleware('can:view-client')->delete('addresses/{address}', [AddressController::class, 'destroy']);

                    Route::middleware('can:view-coupon')->get('coupons', [CouponController::class, 'index']);
                    Route::middleware('can:view-coupon')->get('coupons/{coupon}', [CouponController::class, 'show']);
                    Route::middleware('can:create-coupon')->post('coupons', [CouponController::class, 'store']);
                    Route::middleware('can:edit-coupon')->put('coupons/{coupon}', [CouponController::class, 'update']);
                    Route::middleware('can:delete-coupon')->delete('coupons/{coupon}', [CouponController::class, 'destroy']);


                    Route::middleware('can:view-order')->get('orders', [OrderController::class, 'index']);
                    Route::middleware('can:view-order')->get('orders/{order}', [OrderController::class, 'show']);
                    Route::middleware('can:create-order')->post('orders', [OrderController::class, 'store']);
                    Route::middleware('can:edit-order')->put('orders/{order}', [OrderController::class, 'update']);



                    Route::middleware('can:view-return_order')->get('return-orders', [ReturnOrderController::class, 'index']);
                    Route::middleware('can:view-return_order')->get('return-orders/{returnOrder}', [ReturnOrderController::class, 'show']);
                    Route::middleware('can:create-return_order')->post('return-orders', [ReturnOrderController::class, 'store']);
                    Route::middleware('can:edit-return_order')->put('return-orders/{returnOrder}', [ReturnOrderController::class, 'update']);

                    Route::middleware('can:view-pre_order')->get('pre-orders', [PreOrderController::class, 'index']);
                    Route::middleware('can:view-pre_order')->get('pre-orders/{order}', [PreOrderController::class, 'show']);
                    Route::middleware('can:create-pre_order')->post('pre-orders', [PreOrderController::class, 'store']);
                    Route::middleware('can:edit-pre_order')->put('pre-orders/{order}', [PreOrderController::class, 'update']);

                    Route::middleware('can:view-contacts')->get('contacts', [ContactController::class, 'index']);

                    // Learning Video Routes
                    Route::middleware('can:view-learning_video')->get('learning-videos', [LearningVideoController::class, 'index']);
                    Route::middleware('can:view-learning_video')->get('learning-videos/{learning_video}', [LearningVideoController::class, 'show']);
                    Route::middleware('can:create-learning_video')->post('learning-videos', [LearningVideoController::class, 'store']);
                    Route::middleware('can:view-learning_video')->put('learning-videos/{learning_video}', [LearningVideoController::class, 'update']);
                    Route::middleware('can:view-learning_video')->delete('learning-videos/{learning_video}', [LearningVideoController::class, 'destroy']);



                    Route::middleware('can:view-subscription_plan')->get('subscription-plan', [SubscriptionPlanController::class, 'index']);
                    Route::middleware('can:view-subscription_plan')->get('subscription-plan/{subscription_plan}', [SubscriptionPlanController::class, 'show']);
                    Route::middleware('can:create-subscription_plan')->post('subscription-plan', [SubscriptionPlanController::class, 'store']);
                    Route::middleware('can:edit-subscription_plan')->put('subscription-plan/{subscription_plan}', [SubscriptionPlanController::class, 'update']);
                    Route::middleware('can:delete-subscription_plan')->delete('subscription-plan/{subscription_plan}', [SubscriptionPlanController::class, 'destroy']);

                    Route::middleware('can:view-subscription')->get('subscription', [SubscriptionController::class, 'index']);
                    Route::middleware('can:edit-subscription')->put('subscription/{subscription}', [SubscriptionController::class, 'update']);


                    Route::middleware('can:view-team_member')->get('team-members', [TeamMemberController::class, 'index']);
                    Route::middleware('can:view-team_member')->get('team-members/{team_member}', [TeamMemberController::class, 'show']);
                    Route::middleware('can:create-team_member')->post('team-members', [TeamMemberController::class, 'store']);
                    Route::middleware('can:edit-team_member')->put('team-members/{team_member}', [TeamMemberController::class, 'update']);
                    Route::middleware('can:delete-team_member')->delete('team-members/{team_member}', [TeamMemberController::class, 'destroy']);

                    // Home Section Routes
                    Route::middleware('can:view-home_section')->get('home-sections', [HomeSectionController::class, 'index']);
                    Route::middleware('can:view-home_section')->get('home-sections/{home_section}', [HomeSectionController::class, 'show']);
                    Route::middleware('can:create-home_section')->post('home-sections', [HomeSectionController::class, 'store']);
                    Route::middleware('can:edit-home_section')->put('home-sections/{home_section}', [HomeSectionController::class, 'update']);
                    Route::middleware('can:delete-home_section')->delete('home-sections/{home_section}', [HomeSectionController::class, 'destroy']);

                    Route::middleware('can:edit-home_section')->group(function () {
                        Route::delete('home_banners/{home_banner}', [HomeBannerController::class, 'destroy']);
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

    Route::prefix('client')->group(function () {
        Route::middleware(['manage_client_session', 'app_auth'])->group(function () {
            Route::get('allSettings', [ClientSettingsController::class, 'index']);
            Route::get('home', [ClientHomeController::class, 'index']);
            Route::get('product/{slug}', [ClientProductController::class, 'show']);
            Route::get('shop', [ClientShopController::class, 'index']);
            Route::get('team-members', [ClientTeamMemberController::class, 'index']);
            Route::middleware(['recaptcha'])->group(function () {
                Route::post('send-otp-login', [ClientAuthController::class, 'sendOtpLogin']);
                Route::post('send-otp-register', [ClientAuthController::class, 'sendOtpRegister']);
                Route::post('verify-otp-login', [ClientAuthController::class, 'verifyOtpLogin']);
                Route::post('verify-otp-register', [ClientAuthController::class, 'verifyOtpRegister']);
                Route::post('contact', [ClientContactController::class, 'store']);
                Route::post('newsletterSubscribe', [ClientNewsletterController::class, 'subscribe']);
            });

            Route::middleware('auth:sanctum')->group(function () {
                Route::get('getCurrentUser', [ClientProfileController::class, 'getCurrentUser']);
                Route::post('logout', [ClientProfileController::class, 'logout']);
                Route::post('updateProfile', [ClientProfileController::class, 'update']);

                Route::prefix('cart')->controller(ClientCartController::class)->group(function () {
                    Route::get('/', 'index');
                    Route::post('addOrUpdate', 'addOrUpdate');
                    Route::delete('remove', 'remove');
                });
                Route::prefix('coupon')->controller(ClientCouponController::class)->group(function () {
                    Route::post('apply', 'apply');
                    Route::post('remove', 'remove');
                });

                Route::prefix('addresses')->controller(ClientAddressController::class)->group(function () {
                    Route::get('/', 'index');
                    Route::get('default', 'defaultAddress');
                    Route::get('{address}', 'show');
                    Route::post('/', 'store');
                    Route::put('{id}', 'update');
                    Route::delete('{address}', 'destroy');
                });

                Route::post('placeOrder', [ClientCheckoutController::class, 'placeOrder']);


                Route::get('orders', [ClientOrdersController::class, 'index']);
                Route::get('return-orders', [ClientReturnOrdersController::class, 'index']);
                Route::get('pre-orders', [ClientPreOrdersController::class, 'index']);

                Route::prefix('wishlist')->controller(ClientWishlistController::class)->group(function () {
                    Route::get('/', 'index');
                    Route::post('addOrRemove', 'addOrRemove');
                });



                Route::post('placeOrder', [ClientCheckoutController::class, 'placeOrder']);

                Route::get('learning-videos', [ClientLearningVideoController::class, 'index']);
                Route::get('subscription-plan', [ClientSubscriptionPlanController::class, 'index']);
            });
        });
    });
});

Route::fallback(fn() => abort(404));

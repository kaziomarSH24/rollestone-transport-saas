<?php

use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Admin\CompanyController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\DriverController;
use App\Http\Controllers\Api\V1\Admin\FaqController;
use App\Http\Controllers\Api\V1\Admin\MessageController;
use App\Http\Controllers\Api\V1\Admin\PassengerController;
use App\Http\Controllers\Api\V1\Admin\ReportingController;
use App\Http\Controllers\Api\V1\Admin\RouteController;
use App\Http\Controllers\Api\V1\Admin\SettingsController;
use App\Http\Controllers\Api\V1\Admin\TripController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Http\Controllers\Api\V1\Auth\VerificationController;
use App\Http\Controllers\api\V1\CompanyListController;
use App\Http\Controllers\Api\V1\Driver\JourneyController as DriverJourneyController;
use App\Http\Controllers\Api\V1\Driver\TripController as DriverTripController;
use App\Http\Controllers\api\V1\Passenger\AlertController;
use App\Http\Controllers\Api\V1\Passenger\ContactFormController;
use App\Http\Controllers\Api\V1\Passenger\PassengerFaqController;
use App\Http\Controllers\Api\V1\Passenger\PaymentController;
use App\Http\Controllers\Api\V1\Passenger\RoutesController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Models\Message;


//**---company list---
Route::get('/v1/companies', [CompanyListController::class, 'index'])->name('api.v1.companies.index');

// **--- Public Routes (Authentication) ---
Route::middleware('identify.company')->prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

        Route::post('/verify', [VerificationController::class, 'verify'])->name('api.v1.auth.verify');
        Route::post('/resend-verification', [VerificationController::class, 'resendVerification'])->name('api.v1.auth.resendVerification');

        Route::post('/forgot-password', [PasswordController::class, 'forgotPassword'])->name('api.v1.auth.forgotPassword');
        Route::post('/verify-password-otp', [PasswordController::class, 'verifyResetOtp'])->name('api.v1.auth.verifyResetOtp');
        Route::post('/reset-password-with-token', [PasswordController::class, 'resetPasswordWithToken'])->name('api.v1.auth.resetPasswordWithToken');

        //driver login
        Route::post('/driver/login', [\App\Http\Controllers\Api\V1\Driver\AuthController::class, 'login'])->name('api.v1.driver.auth.login');
    });
});


// **--- Protected Routes (User must be logged in) ---
Route::middleware('auth:sanctum', 'identify.company')->prefix('v1')->group(function () {

    //Transaction related routes
    Route::get('/transactions/history', [TransactionController::class, 'index']);

    // Auth related protected routes
    Route::prefix('auth')->name('api.v1.auth.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/update-password', [PasswordController::class, 'updatePassword'])->name('updatePassword');
    });

    // Profile related protected routes
    Route::prefix('profile')->name('api.v1.profile.')->group(function () {
        Route::get('/me', [ProfileController::class, 'me'])->name('me');
        Route::post('/update', [ProfileController::class, 'updateProfile'])->name('update');
    });

    //** --- Admin Panel Routes ---
    Route::prefix('admin')->name('api.v1.admin.')->group(function () {
        //dashboard routes
        Route::get('/dashboard/live-data', [DashboardController::class, 'getLiveData'])->name('dashboard.liveData');
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');

        //driver management routes
        Route::apiResource('drivers', DriverController::class)->except(['create', 'edit']);


        //->passenger management routes
        Route::apiResource('passengers', PassengerController::class)->except(['create', 'edit']);
        //passenger wallet top-up
        Route::post('passengers/{passenger}/top-up', [PassengerController::class, 'topUpWallet'])->name('passengers.topUp');
        Route::post('/passengers/{passenger}/refund', [PassengerController::class, 'refund'])->name('passengers.refund');


        //company management routes
        Route::apiResource('companies', CompanyController::class)->except(['create', 'edit'])->withoutMiddleware('identify.company');
        //route management routes
        Route::apiResource('routes', RouteController::class)->except(['create', 'edit']);
        //fare management routes

        // trip management routes
        Route::apiResource('trips', TripController::class)->except(['create', 'edit']);

        // Settings Routes
        Route::get('/settings', [SettingsController::class, 'getSettings']);
        Route::post('/settings', [SettingsController::class, 'saveSettings']);
        Route::post('/settings/test-stripe', [SettingsController::class, 'testStripeConnection']);

        // Message Routes
        Route::apiResource('messages', MessageController::class)->except(['create', 'edit']);
        Route::get('messages/dashboard/stats', [MessageController::class, 'dashboardStats']);
        // You can add more message-related routes

        // Reporting Routes
        Route::get('/reports/revenue-by-route', [ReportingController::class, 'revenueByRoute']);
        Route::get('/reports/monthly-trends', [ReportingController::class, 'monthlyTrends']);
        Route::get('/reports/cash-reconciliation', [ReportingController::class, 'getCashReconciliation']);
        Route::post('/reports/cash-reconciliation/check', [ReportingController::class, 'checkCashReconciliation']);
        Route::get('/reports/passenger-analytics', [ReportingController::class, 'passengerAnalytics']);
        Route::get('/reports/route-statistics', [ReportingController::class, 'routeStatistics']);

        // FAQ Management Routes
        Route::apiResource('faqs', FaqController::class)->except(['create', 'edit', 'show']);
    });


    //**--- Passenger Mobile App Routes ---
    Route::prefix('passenger')->name('api.v1.passenger.')->group(function () {
        // Transaction history
        Route::get('/payment/transactions', [PaymentController::class, 'getTransactionHistory'])->name('transactions.history');
        Route::post('/payment/create-card-setup-session', [PaymentController::class, 'createCardSetupSession']);
        // Route::post('/payment/top-up', [PaymentController::class, 'topUpWithSavedCard']);
        // Route::post('/payment/top-up', [PaymentController::class, 'createPaymentIntent']);
        Route::post('/payment/top-up', [PaymentController::class, 'createPaymentSession']);
        Route::post('/payment/refund', [PaymentController::class, 'requestRefund']);
        Route::get('/payment/methods', [PaymentController::class, 'getPaymentMethods']);

        // Routes for passengers
        Route::get('/routes', [RoutesController::class, 'index'])->name('routes.index');
        Route::get('/routes/{id}', [RoutesController::class, 'show'])->name('routes.show');

        //trip alert routes
        Route::get('/alerts', [AlertController::class, 'index']);
        Route::post('/alerts/toggle', [AlertController::class, 'toggleAlert']);
        Route::post('/alerts/fcm-token', [AlertController::class, 'updateFcmToken']);
        Route::get('/alerts/my-alerts', [AlertController::class, 'myAlerts']);

        //contact form route
        Route::post('/contact', [ContactFormController::class, 'submitContactForm']);
        //faq route
        Route::get('/faqs', [PassengerFaqController::class, 'index']);
    });

    // **--- Driver Routes ---
    Route::prefix('driver')->name('api.v1.driver.')->group(function () {
        // Block trip
        Route::get('/routes/{route}/available-trips', [DriverTripController::class, 'getAvailableTrips']);
        Route::post('/trips/block', [DriverJourneyController::class, 'block']);
        Route::get('/trips/find', [DriverTripController::class, 'findByNumber']);
        //driver schedule and journey management
        Route::get('/journeys/driver-schedule', [DriverJourneyController::class, 'getDriverSchedule']);
        Route::post('/journeys/{journey}/start', [DriverJourneyController::class, 'start']);
        Route::post('/journeys/{journey}/end', [DriverJourneyController::class, 'end']);

        Route::post('/journeys/process-payment', [DriverJourneyController::class, 'processPayment']);
        Route::post('/journeys/process-single-payment', [DriverJourneyController::class, 'processSingleUserPayment']);
        Route::post('/journeys/{journey}/update-location', [DriverJourneyController::class, 'updateLocation']);
    });



    //** --- Notification Routes ---
    Route::prefix('notifications')->as('notifications.')->group(function () {

        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/stats', [NotificationController::class, 'stats'])->name('stats');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');

        //custom admin alerts notification
        Route::get('/alerts', [NotificationController::class, 'alerts']);
    });
});

//**--- Webhook Routes ---
Route::post('/v1/stripe/webhook', [WebhookController::class, 'handleStripeWebhook'])->name('webhook.stripe');


// test api route
Route::get('/v1/test', function (Request $request) {
    return (new \App\Http\Controllers\TestController)->index();
})->name('api.v1.test');

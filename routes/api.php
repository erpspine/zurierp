<?php

use App\Http\Controllers\Api\PlatformAuthController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PlatformUserController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TenantAuthController;
use App\Http\Controllers\Api\TenantLeadController;
use App\Http\Controllers\Api\TenantItineraryController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform')->group(function (): void {
    Route::post('login', [PlatformAuthController::class, 'login']);
});

Route::prefix('tenant')->group(function (): void {
    Route::post('login', [TenantAuthController::class, 'login']);
    Route::post('verify-login-otp', [TenantAuthController::class, 'verifyLoginOtp']);
});

Route::prefix('admin')->middleware('auth:platform')->group(function (): void {
    Route::get('/me', [PlatformAuthController::class, 'me']);
    Route::post('/logout', [PlatformAuthController::class, 'logout']);

    Route::get('/platform-roles', [PlatformUserController::class, 'roles']);
    Route::get('/plans', [PlanController::class, 'index']);
    Route::post('/plans', [PlanController::class, 'store']);
    Route::put('/plans/{plan}', [PlanController::class, 'update']);
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy']);
    Route::get('/users', [PlatformUserController::class, 'index']);
    Route::post('/users', [PlatformUserController::class, 'store']);
    Route::put('/users/{platformUser}', [PlatformUserController::class, 'update']);
    Route::delete('/users/{platformUser}', [PlatformUserController::class, 'destroy']);
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/companies/{company}', [CompanyController::class, 'show']);
    Route::post('/companies/{company}', [CompanyController::class, 'update']);
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy']);

    // Subscriptions
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show']);
    Route::get('/subscriptions/{subscription}/invoice', [SubscriptionController::class, 'downloadInvoice']);
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
});

Route::prefix('app')->middleware(['auth:tenant', 'tenant.guard'])->group(function (): void {
    Route::get('/me', [TenantAuthController::class, 'me']);
    Route::post('/logout', [TenantAuthController::class, 'logout']);

    Route::get('/itineraries/dashboard', [TenantItineraryController::class, 'dashboard']);
    Route::get('/itineraries', [TenantItineraryController::class, 'index']);
    Route::post('/itineraries', [TenantItineraryController::class, 'store']);
    Route::get('/itineraries/{itinerary}', [TenantItineraryController::class, 'show']);
    Route::put('/itineraries/{itinerary}', [TenantItineraryController::class, 'update']);
    Route::delete('/itineraries/{itinerary}', [TenantItineraryController::class, 'destroy']);

    Route::get('/leads/dashboard', [TenantLeadController::class, 'dashboard']);
    Route::get('/leads', [TenantLeadController::class, 'index']);
    Route::post('/leads', [TenantLeadController::class, 'store']);
    Route::get('/leads/{lead}', [TenantLeadController::class, 'show']);
    Route::put('/leads/{lead}', [TenantLeadController::class, 'update']);
    Route::delete('/leads/{lead}', [TenantLeadController::class, 'destroy']);
});

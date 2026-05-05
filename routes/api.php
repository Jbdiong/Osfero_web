<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TodolistController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

Route::get('/cities', [LookupController::class, 'getAllCities'])->name('api.v1.cities.all');

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// API Version 1 routes (require authentication via Sanctum)
Route::prefix('v1')->group(function () {
    // Auth Routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/todolists', [TodolistController::class, 'index']);
        Route::post('/todolists', [TodolistController::class, 'store']);
        Route::put('/todolists/{id}', [TodolistController::class, 'update']);
        Route::delete('/todolists/{id}', [TodolistController::class, 'destroy']);
        
        Route::post('/auth/switch-tenant', [AuthController::class, 'switchTenant']);

        Route::get('/events/staff', [TodoController::class, 'getStaff']);

        // Commission routes
        Route::get('/commission/summary', [\App\Http\Controllers\Api\CommissionController::class, 'summary']);
        Route::post('/commission/store', [\App\Http\Controllers\Api\CommissionController::class, 'store']);
        Route::put('/commission/{id}', [\App\Http\Controllers\Api\CommissionController::class, 'update']);
        Route::delete('/commission/{id}', [\App\Http\Controllers\Api\CommissionController::class, 'destroy']);
        Route::post('/commission/{id}/approve', [\App\Http\Controllers\Api\CommissionController::class, 'approve']);
        Route::post('/commission/{id}/reject', [\App\Http\Controllers\Api\CommissionController::class, 'reject']);

        // Leads routes
        Route::get('/leads', [\App\Http\Controllers\LeadController::class, 'apiIndex']);
        Route::post('/leads', [\App\Http\Controllers\LeadController::class, 'apiStore']);
        Route::get('/leads/{id}', [\App\Http\Controllers\LeadController::class, 'apiShow']);
        Route::put('/leads/{id}', [\App\Http\Controllers\LeadController::class, 'apiUpdate']);
        Route::delete('/leads/{id}', [\App\Http\Controllers\LeadController::class, 'apiDestroy']);

        // Inventory routes
        Route::get('/inventory/brands', [\App\Http\Controllers\Api\InventoryController::class, 'getBrands']);
        Route::get('/inventory/folders', [\App\Http\Controllers\Api\InventoryController::class, 'getFolders']);
        Route::post('/inventory/folders', [\App\Http\Controllers\Api\InventoryController::class, 'storeFolder']);
        Route::put('/inventory/folders/{id}/move', [\App\Http\Controllers\Api\InventoryController::class, 'moveFolder']);
        Route::get('/inventory/items', [\App\Http\Controllers\Api\InventoryController::class, 'getItems']);
        Route::post('/inventory/items', [\App\Http\Controllers\Api\InventoryController::class, 'storeItem']);
        Route::put('/inventory/items/{id}', [\App\Http\Controllers\Api\InventoryController::class, 'updateItem']);
        Route::put('/inventory/items/{id}/move', [\App\Http\Controllers\Api\InventoryController::class, 'moveItem']);
        Route::get('/inventory/locations', [\App\Http\Controllers\Api\InventoryController::class, 'getLocations']);
        Route::put('/inventory/variants/{variantId}/stock', [\App\Http\Controllers\Api\InventoryController::class, 'updateVariantStock']);
        Route::put('/inventory/variants/{variantId}', [\App\Http\Controllers\Api\InventoryController::class, 'updateVariant']);

        // Lead dependencies & Lookups
        Route::get('/marketers', [\App\Http\Controllers\Api\LookupController::class, 'getMarketers']);
        Route::get('/lead-statuses', function (Request $request) {
            return app(\App\Http\Controllers\Api\LookupController::class)->getByParent($request, 'Lead_Status');
        });
        Route::get('/payment-statuses', function (Request $request) {
            return app(\App\Http\Controllers\Api\LookupController::class)->getByParent($request, 'Payment_Status');
        });

        // FCM mock
        Route::post('/fcm/store-token', function() {
            return response()->json(['success' => true]);
        });
    });

    // Fallback for unauthenticated requests (fixes Route [login] not defined)
    Route::get('/login', function () {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    })->name('login');
});


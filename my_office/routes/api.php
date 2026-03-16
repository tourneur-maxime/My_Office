<?php

use App\Http\Controllers\ProspectController;
use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route; // Add this line

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/clients/suggestions', [ProspectController::class, 'suggestions'])->name('api.clients.suggestions');

use App\Http\Controllers\Api\ComplianceController; // Add this import

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/prospects/{prospect}/history', [ProspectController::class, 'history'])->name('api.prospects.history');
    Route::post('/quotes/{prospect}', [QuoteController::class, 'store'])->name('api.quotes.store');
    Route::post('/compliance/validate', [ComplianceController::class, 'validateData'])->name('api.compliance.validate'); // Add this route
});

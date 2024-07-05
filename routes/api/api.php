<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganisationController;

Route::middleware('auth:api')->group(function () {
    Route::get('api/users/{id}', [AuthController::class, 'show']);
    Route::get('api/organisations', [OrganisationController::class, 'index']);
    Route::get('api/organisations/{orgId}', [OrganisationController::class, 'show']);
    Route::post('api/organisations', [OrganisationController::class, 'store']);
    Route::post('api/organisations/{orgId}/users', [OrganisationController::class, 'addUser']);
});


<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\adviser\SectionController;
use App\Http\Controllers\adviser\StudentController;
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')
    ->prefix('adviser')
        ->group(function () {

            // Section endpoints
            Route::prefix('sections')->group(function () {

                Route::get('/', [SectionController::class, 'index']);
                Route::post('/', [SectionController::class, 'store']);

                Route::get('/{section}', [SectionController::class, 'show']);
                Route::put('/{section}', [SectionController::class, 'update']);
                Route::delete('/{section}', [SectionController::class, 'destroy']);

            });

            Route::prefix('students')->group(function () {

                Route::get('/', [StudentController::class, 'index']);
                Route::get('/sections', [StudentController::class, 'fetchSection']);
                Route::post('/', [StudentController::class, 'store']);

                // Route::get('/{student}', [StudentController::class, 'show']);
                // Route::put('/{student}', [StudentController::class, 'update']);
                // Route::delete('/{student}', [StudentController::class, 'destroy']);

            });

        },

        
);



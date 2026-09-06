<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\adviser\AuthController as AdviserAuthController;
use App\Http\Controllers\adviser\SectionController;
use App\Http\Controllers\adviser\StudentController;
use App\Http\Controllers\adviser\ModuleController;

use App\Http\Controllers\student\AuthController as StudentAuthController;


/*
|--------------------------------------------------------------------------
| Adviser Authentication
|--------------------------------------------------------------------------
*/

// Adviser Login
Route::post('/login', [AdviserAuthController::class, 'login']);

// Adviser Register
Route::post('/register', [AdviserAuthController::class, 'register']);

// Adviser Protected Routes
Route::middleware('auth:sanctum')->group(function () {

    // Get authenticated adviser
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Adviser Logout
    Route::post('/logout', [AdviserAuthController::class, 'logout']);
});


/*
|--------------------------------------------------------------------------
| Student Authentication
|--------------------------------------------------------------------------
|
| Students authenticate using student_code only.
|
*/

Route::prefix('student')->group(function () {

    // Student Login
    Route::post('/login', [StudentAuthController::class, 'login']);

    /*
    |--------------------------------------------------------------------------
    | Student Protected Routes
    |--------------------------------------------------------------------------
    |
    | These will later use student.auth middleware.
    |
    */

    Route::middleware('student.auth')->group(function () {

        // Get authenticated student
        Route::get('/user', [StudentAuthController::class, 'user']);

        // Student Logout
        Route::post('/logout', [StudentAuthController::class, 'logout']);

    });

});


/*
|--------------------------------------------------------------------------
| Adviser API
|--------------------------------------------------------------------------
|
| All adviser management APIs require Sanctum authentication.
|
*/

Route::middleware('auth:sanctum')
    ->prefix('adviser')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Sections
        |--------------------------------------------------------------------------
        */

        Route::prefix('sections')->group(function () {

            // Get all sections
            Route::get('/', [SectionController::class, 'index']);

            // Get students
            Route::get('/students', [SectionController::class, 'display_student']);

            // Create section
            Route::post('/', [SectionController::class, 'store']);

            // Get specific section
            Route::get('/{section}', [SectionController::class, 'show']);

            // Update section
            Route::put('/{section}', [SectionController::class, 'update']);

            // Delete section
            Route::delete('/{section}', [SectionController::class, 'destroy']);

        });


        /*
        |--------------------------------------------------------------------------
        | Students
        |--------------------------------------------------------------------------
        */

        Route::prefix('students')->group(function () {

            // Get all students
            Route::get('/', [StudentController::class, 'index']);

            // Create student
            Route::post('/', [StudentController::class, 'store']);

            // Get specific student
            Route::get('/{student}', [StudentController::class, 'show']);

            // Update student
            Route::put('/{student}', [StudentController::class, 'update']);

            // Delete student
            Route::delete('/{student}', [StudentController::class, 'destroy']);

        });


        /*
        |--------------------------------------------------------------------------
        | Modules
        |--------------------------------------------------------------------------
        */

        Route::prefix('module')->group(function () {

            // Get all modules
            Route::get('/', [ModuleController::class, 'index']);

            // Create module
            Route::post('/', [ModuleController::class, 'store']);

        });

    });
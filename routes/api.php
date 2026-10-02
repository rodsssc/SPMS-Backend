<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\adviser\AssessmentController as AdviserAssessmentController;
use App\Http\Controllers\adviser\ModuleController;
use App\Http\Controllers\adviser\ScriptController;
use App\Http\Controllers\adviser\SectionController;
use App\Http\Controllers\adviser\StudentController;
use App\Http\Controllers\student\AssessmentController as StudentAssessmentController;
use App\Http\Controllers\student\AuthController as StudentAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user()->makeHidden(['email_verified_at', 'created_at', 'updated_at']));
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
});

Route::prefix('student')->group(function () {
    Route::post('/login', [StudentAuthController::class, 'login']);
    Route::middleware('student.auth')->group(function () {
        Route::get('/user', [StudentAuthController::class, 'user']);
        Route::get('/modules', [StudentAuthController::class, 'modules']);
        Route::get('/assessments', [StudentAssessmentController::class, 'index']);
        Route::get('/assessments/{script}', [StudentAssessmentController::class, 'show']);
        Route::post('/assessments/{script}/submit', [StudentAssessmentController::class, 'submit']);
        Route::get('/assessments/{script}/audio', [StudentAssessmentController::class, 'audio']);
        Route::post('/logout', [StudentAuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum', 'role:adviser'])->prefix('adviser')->group(function () {
    Route::get('/sections', [SectionController::class, 'index']);
    Route::get('/sections/students', [SectionController::class, 'display_student']);
    Route::get('/sections/{section}', [SectionController::class, 'show']);
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{student}', [StudentController::class, 'show']);
    Route::prefix('module')->group(function () {
        Route::get('/', [ModuleController::class, 'index']);
        Route::post('/', [ModuleController::class, 'store']);
        Route::post('/{module}/sections', [ModuleController::class, 'assignSections']);
        Route::get('/{module}/students', [ModuleController::class, 'students']);
    });
    Route::prefix('scripts')->group(function () {
        Route::get('/', [ScriptController::class, 'index']);
        Route::post('/', [ScriptController::class, 'store']);
        Route::get('/{script}', [ScriptController::class, 'show']);
        Route::put('/{script}', [ScriptController::class, 'update']);
        Route::delete('/{script}', [ScriptController::class, 'destroy']);
        Route::put('/{script}/students/{student}', [ScriptController::class, 'updateStudentStatus']);
    });
    Route::get('/assessments', [AdviserAssessmentController::class, 'index']);
    Route::get('/assessments/{assessment}', [AdviserAssessmentController::class, 'show']);
    Route::get('/assessments/{assessment}/audio', [AdviserAssessmentController::class, 'audio']);
});

// Keep legacy management URLs behind the same Super Admin check so advisers
// receive a standard 403 rather than relying on missing UI controls.
Route::middleware(['auth:sanctum', 'role:superadmin'])->prefix('adviser')->group(function () {
    Route::post('/students', [SuperAdminController::class, 'storeStudent']);
    Route::put('/students/{student}', [SuperAdminController::class, 'updateStudent']);
    Route::post('/sections', [SuperAdminController::class, 'storeSection']);
    Route::put('/sections/{section}', [SuperAdminController::class, 'updateSection']);
});

Route::middleware(['auth:sanctum', 'role:superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard']);
    Route::get('/advisers', [SuperAdminController::class, 'advisers']);
    Route::post('/advisers', [SuperAdminController::class, 'storeAdviser']);
    Route::put('/advisers/{user}', [SuperAdminController::class, 'updateAdviser']);
    Route::get('/students', [SuperAdminController::class, 'students']);
    Route::post('/students', [SuperAdminController::class, 'storeStudent']);
    Route::put('/students/{student}', [SuperAdminController::class, 'updateStudent']);
    Route::get('/sections', [SuperAdminController::class, 'sections']);
    Route::post('/sections', [SuperAdminController::class, 'storeSection']);
    Route::put('/sections/{section}', [SuperAdminController::class, 'updateSection']);
    Route::put('/sections/{section}/adviser', [SuperAdminController::class, 'assignAdviser']);
});

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->string('role')->default('adviser')->index(); $table->timestamp('email_verified_at')->nullable();
            $table->string('password'); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); $table->string('token'); $table->timestamp('created_at')->nullable();
        });
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable(); $table->text('user_agent')->nullable();
            $table->longText('payload'); $table->integer('last_activity')->index();
        });
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary(); $table->mediumText('value'); $table->integer('expiration')->index();
        });
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary(); $table->string('owner'); $table->integer('expiration')->index();
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->id(); $table->string('queue')->index(); $table->longText('payload'); $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable(); $table->unsignedInteger('available_at'); $table->unsignedInteger('created_at');
        });
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary(); $table->string('name'); $table->integer('total_jobs'); $table->integer('pending_jobs');
            $table->integer('failed_jobs'); $table->longText('failed_job_ids'); $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable(); $table->integer('created_at'); $table->integer('finished_at')->nullable();
        });
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id(); $table->string('uuid')->unique(); $table->text('connection'); $table->text('queue');
            $table->longText('payload'); $table->longText('exception'); $table->timestamp('failed_at')->useCurrent();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); $table->morphs('tokenable'); $table->text('name'); $table->string('token', 64)->unique();
            $table->text('abilities')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable()->index(); $table->timestamps();
        });
        Schema::create('sections', function (Blueprint $table) {
            $table->id(); $table->foreignId('adviser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('adviser_name')->nullable(); $table->string('year_level'); $table->string('section_name');
            $table->string('school_year'); $table->enum('status', ['active', 'inactive'])->default('active'); $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id(); $table->string('student_code')->unique(); $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('first_name'); $table->string('last_name'); $table->string('middle_name')->nullable();
            $table->string('gender'); $table->date('birthdate'); $table->string('gurdian_name'); $table->string('gurdian_contact');
            $table->string('address'); $table->enum('status', ['active', 'inactive'])->default('active'); $table->timestamps();
        });
        Schema::create('modules', function (Blueprint $table) {
            $table->id(); $table->foreignId('adviser_id')->constrained('users')->cascadeOnDelete(); $table->string('title');
            $table->string('year_level'); $table->string('description')->nullable(); $table->timestamps();
        });
        Schema::create('reading_scripts', function (Blueprint $table) {
            $table->id(); $table->foreignId('module_id')->constrained()->cascadeOnDelete(); $table->string('title');
            $table->text('content')->nullable(); $table->unsignedInteger('word_count')->default(0); $table->date('due_date')->nullable(); $table->timestamps();
        });
        Schema::create('module_section', function (Blueprint $table) {
            $table->id(); $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete(); $table->timestamps();
        });
        Schema::create('student_reading_scripts', function (Blueprint $table) {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reading_script_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'completed', 'non-compliant'])->default('pending'); $table->timestamp('completed_at')->nullable();
            $table->timestamps(); $table->unique(['student_id', 'reading_script_id']);
        });
        Schema::create('assessments', function (Blueprint $table) {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete(); $table->foreignId('reading_script_id')->constrained()->cascadeOnDelete();
            $table->text('script'); $table->text('transcription'); $table->unsignedTinyInteger('accuracy');
            $table->unsignedInteger('total_words'); $table->unsignedInteger('correct_words'); $table->unsignedInteger('incorrect_words');
            $table->string('audio_path'); $table->string('status')->default('completed'); $table->timestamps();
            $table->unique(['student_id', 'reading_script_id']);
        });
    }

    public function down(): void
    {
        foreach (['assessments', 'student_reading_scripts', 'module_section', 'reading_scripts', 'modules', 'students', 'sections', 'personal_access_tokens', 'failed_jobs', 'job_batches', 'jobs', 'cache_locks', 'cache', 'sessions', 'password_reset_tokens', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

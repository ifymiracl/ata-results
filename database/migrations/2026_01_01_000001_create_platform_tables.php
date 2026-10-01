<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_platform_admin')->default(false);
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });

        Schema::create('schools', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('short_name')->nullable();
            $t->string('slug')->unique();
            $t->string('code', 30)->unique();
            $t->string('address')->nullable();
            $t->string('state')->nullable();
            $t->string('country')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('motto')->nullable();
            $t->string('logo_path')->nullable();
            $t->json('grading_scale')->nullable();
            $t->json('score_weights')->nullable(); // {ca1,ca2,exam}
            $t->decimal('promotion_benchmark', 5, 2)->default(40);
            $t->string('current_session', 20)->nullable();
            $t->string('current_term', 20)->nullable();
            $t->date('next_term_begins')->nullable();
            $t->string('plan_name')->nullable();
            $t->dateTime('trial_ends_at')->nullable();
            $t->dateTime('subscription_paid_until')->nullable();
            $t->string('paystack_public_key')->nullable();
            $t->text('paystack_secret_key')->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
        });

        Schema::create('slug_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('old_slug')->index();
            $t->timestamps();
        });

        Schema::create('staff', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('staff_code', 40);
            $t->string('name');
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('gender', 10)->nullable();
            $t->string('position_title')->nullable();
            $t->string('role', 30);
            $t->string('assigned_class')->nullable();
            $t->string('pin_hash');
            $t->boolean('must_change_pin')->default(true);
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'staff_code']);
        });

        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unique(['school_id', 'name']);
        });

        Schema::create('school_classes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->integer('sort_order')->default(0);
            $t->unsignedInteger('capacity')->nullable();
            $t->unique(['school_id', 'name']);
        });

        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->boolean('is_core')->default(true);
            $t->unique(['school_id', 'name']);
        });

        Schema::create('class_subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('class_name');
            $t->string('subject');
            $t->unique(['school_id', 'class_name', 'subject']);
        });

        Schema::create('department_subjects', function (Blueprint $t) {
            $t->foreignId('department_id')->constrained()->cascadeOnDelete();
            $t->string('subject');
            $t->primary(['department_id', 'subject']);
        });

        Schema::create('staff_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $t->string('class_name');
            $t->string('subject');
            $t->unique(['staff_id', 'class_name', 'subject']);
        });

        Schema::create('students', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('admission_no', 60);
            $t->string('first_name');
            $t->string('middle_name')->nullable();
            $t->string('last_name');
            $t->string('gender', 10)->nullable();
            $t->string('class_name');
            $t->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $t->string('guardian_name')->nullable();
            $t->string('guardian_relationship', 30)->nullable();
            $t->string('guardian_phone')->nullable();
            $t->string('guardian_email')->nullable();
            $t->string('address')->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('photo_path')->nullable();
            $t->string('pin_hash');
            $t->boolean('must_change_pin')->default(true);
            $t->string('parent_pin_hash')->nullable();
            $t->boolean('parent_must_change_pin')->default(true);
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'admission_no']);
            $t->index(['school_id', 'class_name']);
        });

        Schema::create('results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->string('subject');
            $t->string('session_label', 20);
            $t->string('term', 20);
            $t->decimal('ca1', 5, 2)->default(0);
            $t->decimal('ca2', 5, 2)->default(0);
            $t->decimal('exam', 5, 2)->default(0);
            $t->decimal('total', 5, 2)->default(0);
            $t->string('grade', 5)->nullable();
            $t->string('remark', 60)->nullable();
            $t->string('status', 20)->default('draft');
            $t->unsignedBigInteger('entered_by')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'student_id', 'subject', 'session_label', 'term'], 'one_result');
            $t->index(['school_id', 'session_label', 'term']);
        });

        Schema::create('class_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('class_name');
            $t->string('session_label', 20);
            $t->string('term', 20);
            $t->string('status', 20)->default('waiting');
            $t->text('form_teacher_comment')->nullable();
            $t->text('principal_comment')->nullable();
            $t->dateTime('reviewed_at')->nullable();
            $t->dateTime('published_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'class_name', 'session_label', 'term'], 'one_review');
        });

        Schema::create('student_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->string('session_label', 20);
            $t->string('term', 20);
            $t->string('teacher_comment', 500)->nullable();
            $t->string('principal_comment', 500)->nullable();
            $t->unique(['student_id', 'session_label', 'term']);
        });

        Schema::create('report_codes', function (Blueprint $t) {
            $t->string('code', 40)->primary();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->string('session_label', 20);
            $t->string('term', 20);
            $t->timestamps();
            $t->unique(['student_id', 'session_label', 'term']);
        });

        Schema::create('annual_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->string('session_label', 20);
            $t->decimal('annual_average', 6, 2)->default(0);
            $t->unsignedInteger('class_position')->nullable();
            $t->unsignedInteger('class_population')->nullable();
            $t->unsignedInteger('level_position')->nullable();
            $t->unsignedInteger('level_population')->nullable();
            $t->string('recommended_action', 20)->nullable();
            $t->string('recommended_class')->nullable();
            $t->boolean('applied')->default(false);
            $t->timestamps();
            $t->unique(['school_id', 'student_id', 'session_label']);
        });

        Schema::create('academic_awards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->string('session_label', 20);
            $t->string('award_type', 30);
            $t->string('subject')->nullable();
            $t->string('level_label');
            $t->timestamps();
        });

        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('label');
            $t->string('class_name')->nullable();
            $t->string('session_label', 20);
            $t->string('term', 20);
            $t->decimal('amount', 12, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('student_fees', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('fee_structure_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount_due', 12, 2)->default(0);
            $t->decimal('amount_paid', 12, 2)->default(0);
            $t->string('status', 20)->default('unpaid');
            $t->timestamps();
            $t->unique(['student_id', 'fee_structure_id']);
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_fee_id')->nullable()->constrained()->nullOnDelete();
            $t->string('reference')->unique();
            $t->decimal('amount', 12, 2);
            $t->string('method', 20)->default('paystack'); // paystack|cash|transfer
            $t->string('status', 20)->default('pending');
            $t->string('recorded_by')->nullable();
            $t->timestamps();
        });

        Schema::create('subscription_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('reference')->unique();
            $t->unsignedInteger('student_count');
            $t->decimal('amount', 12, 2);
            $t->string('status', 20)->default('pending');
            $t->timestamps();
        });

        Schema::create('onboarding_requests', function (Blueprint $t) {
            $t->id();
            $t->string('school_name');
            $t->string('contact_name');
            $t->string('contact_role')->nullable();
            $t->string('student_count', 30)->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('state')->nullable();
            $t->string('country')->nullable();
            $t->string('levels')->nullable();
            $t->text('message')->nullable();
            $t->string('status', 20)->default('pending');
            $t->text('admin_notes')->nullable();
            $t->foreignId('created_school_id')->nullable();
            $t->timestamps();
        });

        // ---- new features ----
        Schema::create('attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->string('class_name');
            $t->date('date');
            $t->string('status', 10); // present|absent|late|excused
            $t->string('note')->nullable();
            $t->unsignedBigInteger('marked_by')->nullable();
            $t->unique(['student_id', 'date']);
            $t->index(['school_id', 'class_name', 'date']);
        });

        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('body');
            $t->string('audience', 20)->default('all'); // all|staff|students|parents
            $t->string('class_name')->nullable();
            $t->boolean('pinned')->default(false);
            $t->string('author')->nullable();
            $t->timestamps();
        });

        Schema::create('skill_ratings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->string('session_label', 20);
            $t->string('term', 20);
            $t->string('skill');
            $t->unsignedTinyInteger('rating'); // 1-5
            $t->unique(['student_id', 'session_label', 'term', 'skill'], 'one_skill');
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('actor');
            $t->string('action', 60);
            $t->string('detail')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamps();
            $t->index(['school_id', 'created_at']);
        });
    }
};

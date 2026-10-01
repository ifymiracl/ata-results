<?php

use App\Http\Controllers as C;
use App\Http\Controllers\Platform as P;
use Illuminate\Support\Facades\Route;

// ---------- public ----------
Route::get('/', [C\PublicController::class, 'home'])->name('home');
Route::get('/find-school', [C\PublicController::class, 'find'])->name('find');
Route::get('/register-school', [C\PublicController::class, 'registerForm'])->name('register');
Route::post('/register-school', [C\PublicController::class, 'register'])->name('register.store')->middleware('throttle:6,1');
Route::get('/verify', [C\PublicController::class, 'verify'])->name('verify');
Route::post('/webhooks/paystack', [C\WebhookController::class, 'paystack'])->name('webhooks.paystack');

// ---------- platform console ----------
Route::prefix('platform')->name('platform.')->group(function () {
    Route::get('login', [P\PlatformController::class, 'loginForm'])->name('login');
    Route::post('login', [P\PlatformController::class, 'login'])->middleware('throttle:10,1');
    Route::middleware('platform')->group(function () {
        Route::post('logout', [P\PlatformController::class, 'logout'])->name('logout');
        Route::get('/', [P\PlatformController::class, 'dashboard'])->name('dashboard');
        Route::get('requests', [P\PlatformController::class, 'requests'])->name('requests');
        Route::post('requests/{req}', [P\PlatformController::class, 'updateRequest'])->name('requests.update');
        Route::post('requests/{req}/create-school', [P\PlatformController::class, 'createSchool'])->name('requests.create');
        Route::get('schools', [P\PlatformController::class, 'schools'])->name('schools');
        Route::post('schools/{id}', [P\PlatformController::class, 'updateSchool'])->name('schools.update');
        Route::get('settings', [P\PlatformController::class, 'settings'])->name('settings');
        Route::post('settings', [P\PlatformController::class, 'saveSettings'])->name('settings.save');
        Route::get('audit', [P\PlatformController::class, 'audit'])->name('audit');
    });
});

// ---------- school portal ----------
$reserved = '^(?!platform$|find-school$|register-school$|verify$|webhooks$|up$|storage$|build$)[a-z0-9-]+$';

Route::prefix('{school}')->where(['school' => $reserved])->middleware(['web', 'school'])->group(function () {
    Route::get('/', [C\AuthController::class, 'landing'])->name('school');
    Route::get('login', [C\AuthController::class, 'loginForm'])->name('login');
    Route::post('login', [C\AuthController::class, 'login'])->name('login.post');
    Route::post('forgot', [C\AuthController::class, 'forgot'])->name('forgot')->middleware('throttle:5,1');
    Route::get('r/{code}', [C\ReportCardController::class, 'publicVerify'])->name('report.public');

    Route::middleware('portal')->group(function () {
        Route::post('logout', [C\AuthController::class, 'logout'])->name('logout');
        Route::get('pin', [C\AuthController::class, 'pinForm'])->name('pin.form');
        Route::post('pin', [C\AuthController::class, 'pinSave'])->name('pin.save');
        Route::get('home', [C\DashboardController::class, 'home'])->name('home.school');
        Route::get('announcements', [C\AnnouncementController::class, 'index'])->name('announcements');
        Route::get('report/{student}', [C\ReportCardController::class, 'show'])->name('report');
        Route::get('id-card/{student}', [C\IdCardController::class, 'show'])->name('idcard');
    });

    // student & parent
    Route::middleware('portal:family')->group(function () {
        Route::get('me/results', [C\FamilyController::class, 'results'])->name('family.results');
        Route::get('me/fees', [C\FamilyController::class, 'fees'])->name('family.fees');
        Route::post('me/fees/{fee}/pay', [C\FeeController::class, 'pay'])->name('family.pay');
        Route::get('me/attendance', [C\FamilyController::class, 'attendance'])->name('family.attendance');
    });
    Route::get('pay/callback', [C\FeeController::class, 'callback'])->name('pay.callback');

    // teaching staff
    Route::middleware('portal:staff,subject_teacher,form_teacher,principal,school_admin')->group(function () {
        Route::get('scores', [C\ResultsController::class, 'index'])->name('scores');
        Route::get('scores/sheet', [C\ResultsController::class, 'sheet'])->name('scores.sheet');
        Route::post('scores/save', [C\ResultsController::class, 'save'])->name('scores.save');
        Route::post('scores/submit', [C\ResultsController::class, 'submit'])->name('scores.submit');
        Route::get('scores/template', [C\ResultsController::class, 'template'])->name('scores.template');
        Route::post('scores/import', [C\ResultsController::class, 'import'])->name('scores.import');
        Route::get('classes/{class}/students', [C\StudentController::class, 'classList'])->name('class.list');
        Route::get('attendance', [C\AttendanceController::class, 'index'])->name('attendance');
        Route::post('attendance', [C\AttendanceController::class, 'save'])->name('attendance.save');
    });

    // reviewers
    Route::middleware('portal:staff,form_teacher,principal,school_admin')->group(function () {
        Route::get('review', [C\ReviewController::class, 'index'])->name('review');
        Route::get('review/class', [C\ReviewController::class, 'show'])->name('review.class');
        Route::post('review/action', [C\ReviewController::class, 'act'])->name('review.act');
        Route::get('review/skills', [C\SkillController::class, 'index'])->name('skills');
        Route::post('review/skills', [C\SkillController::class, 'save'])->name('skills.save');
        Route::get('review/print', [C\ReportCardController::class, 'printClass'])->name('report.class');
        Route::get('analytics', [C\AnalyticsController::class, 'index'])->name('analytics');
        Route::get('export/results', [C\AnalyticsController::class, 'exportResults'])->name('export.results');
    });

    // school admin
    Route::middleware('portal:staff,school_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [C\AdminController::class, 'index'])->name('index');
        Route::post('settings', [C\AdminController::class, 'saveSettings'])->name('settings');
        Route::post('classes', [C\AdminController::class, 'saveClass'])->name('classes');
        Route::post('classes/{id}/delete', [C\AdminController::class, 'deleteClass'])->name('classes.delete');
        Route::post('subjects', [C\AdminController::class, 'saveSubject'])->name('subjects');
        Route::post('subjects/{id}/delete', [C\AdminController::class, 'deleteSubject'])->name('subjects.delete');
        Route::post('class-subjects', [C\AdminController::class, 'saveClassSubjects'])->name('classsubjects');
        Route::post('departments', [C\AdminController::class, 'saveDepartment'])->name('departments');
        Route::post('departments/{id}/delete', [C\AdminController::class, 'deleteDepartment'])->name('departments.delete');

        Route::get('staff', [C\StaffController::class, 'index'])->name('staff');
        Route::post('staff', [C\StaffController::class, 'store'])->name('staff.store');
        Route::post('staff/import', [C\StaffController::class, 'import'])->name('staff.import');
        Route::post('staff/{id}', [C\StaffController::class, 'update'])->name('staff.update');
        Route::post('staff/{id}/reset', [C\StaffController::class, 'resetPin'])->name('staff.reset');
        Route::post('staff/{id}/assign', [C\StaffController::class, 'assign'])->name('staff.assign');
        Route::post('assignments/{id}/delete', [C\StaffController::class, 'unassign'])->name('assignments.delete');

        Route::get('students', [C\StudentController::class, 'index'])->name('students');
        Route::post('students', [C\StudentController::class, 'store'])->name('students.store');
        Route::post('students/import', [C\StudentController::class, 'import'])->name('students.import');
        Route::get('students/export', [C\StudentController::class, 'export'])->name('students.export');
        Route::post('students/{id}', [C\StudentController::class, 'update'])->name('students.update');
        Route::post('students/{id}/reset', [C\StudentController::class, 'resetPin'])->name('students.reset');

        Route::get('annual', [C\AnnualController::class, 'index'])->name('annual');
        Route::post('annual/compute', [C\AnnualController::class, 'compute'])->name('annual.compute');
        Route::post('annual/apply', [C\AnnualController::class, 'apply'])->name('annual.apply');

        Route::get('fees', [C\FeeController::class, 'index'])->name('fees');
        Route::post('fees', [C\FeeController::class, 'store'])->name('fees.store');
        Route::post('fees/{id}/delete', [C\FeeController::class, 'destroy'])->name('fees.delete');
        Route::post('fees/record', [C\FeeController::class, 'record'])->name('fees.record');

        Route::get('billing', [C\BillingController::class, 'index'])->name('billing');
        Route::post('billing/pay', [C\BillingController::class, 'pay'])->name('billing.pay');
        Route::get('billing/callback', [C\BillingController::class, 'callback'])->name('billing.callback');

        Route::post('announcements', [C\AnnouncementController::class, 'store'])->name('announcements.store');
        Route::post('announcements/{id}/delete', [C\AnnouncementController::class, 'destroy'])->name('announcements.delete');
        Route::get('audit', [C\AnnouncementController::class, 'audit'])->name('audit');
    });
});

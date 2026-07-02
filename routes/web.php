<?php

use App\Http\Controllers\Auth\AupController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpChallengeController;
use App\Http\Controllers\Public\ApplicationController;
use App\Http\Controllers\Public\CategoryController;
use App\Http\Controllers\Shared\AccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/apply', [CategoryController::class, 'index'])->name('apply');
Route::post('/apply', [ApplicationController::class, 'store']);

Route::get('/track', [ApplicationController::class, 'track'])->name('track');
Route::get('/track/{referenceCode}', [ApplicationController::class, 'show'])->name('track.show');
Route::post('/track/{referenceCode}/resubmit', [ApplicationController::class, 'resubmit'])->name('track.resubmit');

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/forgot-password', function () {
        return Inertia::render('Auth/ForgotPassword');
    })->name('password.request');
    Route::get('/otp-challenge', [OtpChallengeController::class, 'show'])->name('otp.challenge');
    Route::post('/otp-challenge', [OtpChallengeController::class, 'verify']);
    Route::post('/otp-challenge/resend', [OtpChallengeController::class, 'resend'])->name('otp.resend');
});

// Authenticated routes (any role)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/acceptable-use-policy', [AupController::class, 'show'])->name('aup.show');
    Route::post('/acceptable-use-policy', [AupController::class, 'accept'])->name('aup.accept');
});

// Authenticated + AUP accepted routes
Route::middleware(['auth', 'aup.accepted'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Account settings
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::post('/account', [AccountController::class, 'update'])->name('account.update');
    Route::get('/account/profile-picture', [AccountController::class, 'profilePicture'])->name('account.profile-picture');

    // Admin panel
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');
        Route::get('/analytics', function () {
            return Inertia::render('Admin/Analytics');
        })->name('analytics');
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        Route::patch('/users/{user}/toggle-status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::delete('/users/{user}/sessions', [\App\Http\Controllers\Admin\UserController::class, 'revokeSessions'])->name('users.revoke-sessions');
        Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs');
        Route::get('/audit-logs/export', [\App\Http\Controllers\Admin\AuditLogController::class, 'export'])->name('audit-logs.export');
        Route::get('/settings', [\App\Http\Controllers\Admin\SystemSettingController::class, 'index'])->name('settings');
        Route::put('/settings', [\App\Http\Controllers\Admin\SystemSettingController::class, 'update'])->name('settings.update');
        Route::resource('assistance-categories', \App\Http\Controllers\Admin\AssistanceCategoryController::class);
        Route::resource('required-documents', \App\Http\Controllers\Admin\RequiredDocumentController::class);
        Route::resource('assistance-code-references', \App\Http\Controllers\Admin\AssistanceCodeReferenceController::class);
    });

    // AICS Staff panel
    Route::middleware('role:aics_staff')->prefix('aics')->name('aics.')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');
        Route::get('/analytics', function () {
            return Inertia::render('Aics/Analytics');
        })->name('analytics');
        Route::get('/applications', [\App\Http\Controllers\Aics\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [\App\Http\Controllers\Aics\ApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/documents/{document}/url', [\App\Http\Controllers\Aics\ApplicationController::class, 'documentUrl'])->name('applications.document-url');
        Route::post('/applications/{application}/approve', [\App\Http\Controllers\Aics\ApplicationController::class, 'approve'])->name('applications.approve');
        Route::post('/applications/{application}/return', [\App\Http\Controllers\Aics\ApplicationController::class, 'return'])->name('applications.return');
        Route::get('/assistance-codes', [\App\Http\Controllers\Aics\AssistanceCodeController::class, 'index'])->name('assistance-codes.index');
        Route::get('/assistance-codes/{application}', [\App\Http\Controllers\Aics\AssistanceCodeController::class, 'show'])->name('assistance-codes.show');
        Route::post('/assistance-codes/{application}/code', [\App\Http\Controllers\Aics\AssistanceCodeController::class, 'store'])->name('assistance-codes.store');
    });

    // MSWDO panel
    Route::middleware('role:mswdo')->prefix('mswdo')->name('mswdo.')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');
        Route::get('/analytics', function () {
            return Inertia::render('Mswdo/Analytics');
        })->name('analytics');
        Route::get('/applications', [\App\Http\Controllers\Mswdo\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [\App\Http\Controllers\Mswdo\ApplicationController::class, 'show'])->name('applications.show');
        Route::post('/applications/{application}/approve', [\App\Http\Controllers\Mswdo\ApplicationController::class, 'approve'])->name('applications.approve');
        Route::post('/applications/{application}/return', [\App\Http\Controllers\Mswdo\ApplicationController::class, 'return'])->name('applications.return');
        Route::get('/vouchers', [\App\Http\Controllers\Mswdo\VoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/{application}', [\App\Http\Controllers\Mswdo\VoucherController::class, 'show'])->name('vouchers.show');
        Route::post('/vouchers/{application}', [\App\Http\Controllers\Mswdo\VoucherController::class, 'store'])->name('vouchers.store');
    });

    // Accountant panel
    Route::middleware('role:accountant')->prefix('accountant')->name('accountant.')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');
        Route::get('/analytics', function () {
            return Inertia::render('Accountant/Analytics');
        })->name('analytics');
        Route::get('/vouchers', [\App\Http\Controllers\Accountant\VoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/{voucher}', [\App\Http\Controllers\Accountant\VoucherController::class, 'show'])->name('vouchers.show');
        Route::post('/vouchers/{voucher}/approve', [\App\Http\Controllers\Accountant\VoucherController::class, 'approve'])->name('vouchers.approve');
        Route::post('/vouchers/{voucher}/return', [\App\Http\Controllers\Accountant\VoucherController::class, 'return'])->name('vouchers.return');
    });

    // Treasurer panel
    Route::middleware('role:treasurer')->prefix('treasurer')->name('treasurer.')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');
        Route::get('/analytics', function () {
            return Inertia::render('Treasurer/Analytics');
        })->name('analytics');
        Route::get('/cheques', [\App\Http\Controllers\Treasurer\ChequeController::class, 'index'])->name('cheques.index');
        Route::get('/cheques/{voucher}', [\App\Http\Controllers\Treasurer\ChequeController::class, 'show'])->name('cheques.show');
        Route::post('/cheques/{voucher}/acknowledge', [\App\Http\Controllers\Treasurer\ChequeController::class, 'acknowledge'])->name('cheques.acknowledge');
        Route::get('/budget', [\App\Http\Controllers\Treasurer\BudgetController::class, 'index'])->name('budget.index');
        Route::get('/budget/{voucher}', [\App\Http\Controllers\Treasurer\BudgetController::class, 'show'])->name('budget.show');
        Route::post('/budget/{voucher}/mark-ready', [\App\Http\Controllers\Treasurer\BudgetController::class, 'markReady'])->name('budget.mark-ready');
        Route::post('/budget/{voucher}/hold', [\App\Http\Controllers\Treasurer\BudgetController::class, 'hold'])->name('budget.hold');
        Route::post('/budget/{voucher}/re-evaluate', [\App\Http\Controllers\Treasurer\BudgetController::class, 'reEvaluate'])->name('budget.re-evaluate');
    });

    // Mayor's Office panel
    Route::middleware('role:mayors_office')->prefix('mayors-office')->name('mayors-office.')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');
        Route::get('/analytics', function () {
            return Inertia::render('MayorsOffice/Analytics');
        })->name('analytics');
    });
});

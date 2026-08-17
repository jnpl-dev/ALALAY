<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AssistanceCategoryController;
use App\Http\Controllers\Admin\AssistanceCodeReferenceController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RequiredDocumentController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Accountant\VoucherController as AccountantVoucherController;
use App\Http\Controllers\Auth\AupController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpChallengeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Public\ApplicationController;
use App\Http\Controllers\Public\CategoryController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Shared\AccountController;
use App\Http\Controllers\PendingCountController;
use App\Http\Controllers\ValidationController;
use App\Http\Controllers\Aics\AnalyticsController as AicsAnalyticsController;
use App\Http\Controllers\Aics\ApplicationController as AicsApplicationController;
use App\Http\Controllers\Aics\AssistanceCodeController;
use App\Http\Controllers\Aics\DashboardController as AicsDashboardController;
use App\Http\Controllers\Mswdo\AnalyticsController as MswdoAnalyticsController;
use App\Http\Controllers\Mswdo\ApplicationController as MswdoApplicationController;
use App\Http\Controllers\Mswdo\DashboardController as MswdoDashboardController;
use App\Http\Controllers\Mswdo\VoucherController as MswdoVoucherController;
use App\Http\Controllers\Accountant\DashboardController as AccountantDashboardController;
use App\Http\Controllers\Accountant\AnalyticsController as AccountantAnalyticsController;
use App\Http\Controllers\Treasurer\ChequeController as TreasurerChequeController;
use App\Http\Controllers\Treasurer\DashboardController as TreasurerDashboardController;
use App\Http\Controllers\Treasurer\AnalyticsController as TreasurerAnalyticsController;
use App\Http\Controllers\InternalAudit\AnalyticsController as InternalAuditAnalyticsController;
use App\Http\Controllers\InternalAudit\CodingReviewController;
use App\Http\Controllers\InternalAudit\DashboardController as InternalAuditDashboardController;
use App\Http\Controllers\BudgetOffice\AnalyticsController as BudgetOfficeAnalyticsController;
use App\Http\Controllers\BudgetOffice\DashboardController as BudgetOfficeDashboardController;
use App\Http\Controllers\BudgetOffice\VoucherController as BudgetOfficeVoucherController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::post('/contact', [ContactController::class, 'send'])->name('contact.send')->middleware('throttle:contact');

Route::get('/apply', [CategoryController::class, 'index'])->name('apply');
Route::post('/apply', [ApplicationController::class, 'store'])->middleware('throttle:application_submit');

Route::get('/track', [ApplicationController::class, 'track'])->name('track');
Route::get('/track/poll', [ApplicationController::class, 'trackPoll'])->name('track.poll')->middleware('throttle:track_poll');
Route::get('/track/{referenceCode}', [ApplicationController::class, 'show'])->name('track.show')->middleware('throttle:track_show');
Route::post('/track/{referenceCode}/send-otp', [ApplicationController::class, 'sendTrackOtp'])->name('track.send-otp')->middleware('throttle:3,5');
Route::post('/track/{referenceCode}/verify-otp', [ApplicationController::class, 'verifyTrackOtp'])->name('track.verify-otp')->middleware('throttle:10,5');
Route::post('/track/{referenceCode}/resubmit', [ApplicationController::class, 'resubmit'])->name('track.resubmit')->middleware('throttle:resubmit');

// Real-time validation (public)
Route::get('/validate/beneficiary', [ValidationController::class, 'checkBeneficiary'])->name('validate.beneficiary')->middleware('throttle:public_validate');
Route::get('/validate/phone', [ValidationController::class, 'checkPhone'])->name('validate.phone')->middleware('throttle:public_validate');
Route::get('/validate/email', [ValidationController::class, 'checkEmail'])->name('validate.email')->middleware('throttle:public_validate');
Route::get('/validate/reference-code', [ValidationController::class, 'checkReferenceCode'])->name('validate.reference-code')->middleware('throttle:public_validate');

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/forgot-password', function () {
        return Inertia::render('Auth/ForgotPassword');
    })->name('password.request');
    Route::post('/forgot-password', [\Laravel\Fortify\Http\Controllers\PasswordResetLinkController::class, 'store'])->name('password.email')->middleware('throttle:forgot_password');
    Route::get('/reset-password/{token}', function (Request $request) {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $request->token,
            'email' => $request->email,
        ]);
    })->name('password.reset');
    Route::post('/reset-password', [\Laravel\Fortify\Http\Controllers\NewPasswordController::class, 'store'])->name('password.update')->middleware('throttle:reset_password');
    Route::get('/otp-challenge', [OtpChallengeController::class, 'show'])->name('otp.challenge');
    Route::post('/otp-challenge', [OtpChallengeController::class, 'verify'])->name('otp.verify')->middleware('throttle:otp_verify');
    Route::post('/otp-challenge/resend', [OtpChallengeController::class, 'resend'])->name('otp.resend')->middleware('throttle:otp_resend');
});

// Authenticated routes (any role)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/acceptable-use-policy', [AupController::class, 'show'])->name('aup.show');
    Route::post('/acceptable-use-policy', [AupController::class, 'accept'])->name('aup.accept');
});

// Authenticated + AUP accepted routes
Route::middleware(['auth', 'aup.accepted'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pending counts (polling)
    Route::get('/pending-counts', [PendingCountController::class, 'poll'])->name('pending-counts');

    // Account settings
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::post('/account', [AccountController::class, 'update'])->name('account.update');
    Route::get('/account/profile-picture', [AccountController::class, 'profilePicture'])->name('account.profile-picture');

    // Admin panel
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::delete('/users/{user}/sessions', [UserController::class, 'revokeSessions'])->name('users.revoke-sessions');
        Route::get('/users/{user}/profile-picture', [UserController::class, 'profilePicture'])->name('users.profile-picture');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
        Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
        Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings');
        Route::put('/settings', [SystemSettingController::class, 'update'])->name('settings.update');
        Route::post('/maintenance/toggle', [SystemSettingController::class, 'toggleMaintenance'])->name('maintenance.toggle');
        Route::get('/sms/updates', [SmsController::class, 'updates'])->name('sms.updates');
        Route::post('/sms/updates', [SmsController::class, 'saveUpdates']);
        Route::get('/sms/claiming', [SmsController::class, 'claiming'])->name('sms.claiming');
        Route::post('/sms/claiming/template', [SmsController::class, 'saveClaimingTemplate'])->name('sms.claiming.template');
        Route::post('/sms/claiming/trigger', [SmsController::class, 'triggerClaiming'])->name('sms.claiming.trigger');
        Route::resource('assistance-categories', AssistanceCategoryController::class);
        Route::resource('required-documents', RequiredDocumentController::class);
        Route::resource('assistance-code-references', AssistanceCodeReferenceController::class);
    });

    // AICS Staff panel
    Route::middleware('role:aics_staff')->prefix('aics')->name('aics.')->group(function () {
        Route::get('/dashboard', [AicsDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [AicsAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/applications', [AicsApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/create', [AicsApplicationController::class, 'create'])->name('applications.create');
        Route::post('/applications', [AicsApplicationController::class, 'storeAssisted'])->name('applications.store-assisted')->middleware('throttle:assisted_submit');
        Route::get('/applications/export', [AicsApplicationController::class, 'export'])->name('applications.export');
        Route::get('/applications/poll', [AicsApplicationController::class, 'poll'])->name('applications.poll');
        Route::get('/applications/{application}', [AicsApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/documents/{document}/url', [AicsApplicationController::class, 'documentUrl'])->name('applications.document-url');
        Route::post('/applications/{application}/approve', [AicsApplicationController::class, 'approve'])->name('applications.approve');
        Route::post('/applications/{application}/return', [AicsApplicationController::class, 'return'])->name('applications.return');
        Route::get('/assistance-codes', [AssistanceCodeController::class, 'index'])->name('assistance-codes.index');
        Route::get('/assistance-codes/export', [AssistanceCodeController::class, 'export'])->name('assistance-codes.export');
        Route::get('/assistance-codes/poll', [AssistanceCodeController::class, 'poll'])->name('assistance-codes.poll');
        Route::get('/assistance-codes/{application}', [AssistanceCodeController::class, 'show'])->name('assistance-codes.show');
        Route::post('/assistance-codes/{application}/code', [AssistanceCodeController::class, 'store'])->name('assistance-codes.store');
    });

    // MSWDO panel
    Route::middleware('role:mswdo')->prefix('mswdo')->name('mswdo.')->group(function () {
        Route::get('/dashboard', [MswdoDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [MswdoAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/applications', [MswdoApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/export', [MswdoApplicationController::class, 'export'])->name('applications.export');
        Route::get('/applications/poll', [MswdoApplicationController::class, 'poll'])->name('applications.poll');
        Route::get('/applications/{application}', [MswdoApplicationController::class, 'show'])->name('applications.show');
        Route::post('/applications/{application}/approve', [MswdoApplicationController::class, 'approve'])->name('applications.approve');
        Route::post('/applications/{application}/return', [MswdoApplicationController::class, 'return'])->name('applications.return');
        Route::get('/applications/{application}/document/{document}/url', [MswdoApplicationController::class, 'documentUrl'])->name('applications.document-url');
        Route::get('/applications/{application}/case-study/url', [MswdoApplicationController::class, 'caseStudyUrl'])->name('applications.case-study-url');
        Route::get('/vouchers', [MswdoVoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/export', [MswdoVoucherController::class, 'export'])->name('vouchers.export');
        Route::get('/vouchers/poll', [MswdoVoucherController::class, 'poll'])->name('vouchers.poll');
        Route::get('/vouchers/{application}', [MswdoVoucherController::class, 'show'])->name('vouchers.show');
        Route::post('/vouchers/{application}', [MswdoVoucherController::class, 'store'])->name('vouchers.store');
        Route::get('/vouchers/{application}/url', [MswdoVoucherController::class, 'voucherUrl'])->name('vouchers.url');
    });

    // Accountant panel
    Route::middleware('role:accountant')->prefix('accountant')->name('accountant.')->group(function () {
        Route::get('/dashboard', [AccountantDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [AccountantAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/vouchers', [AccountantVoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/export', [AccountantVoucherController::class, 'export'])->name('vouchers.export');
        Route::get('/vouchers/poll', [AccountantVoucherController::class, 'poll'])->name('vouchers.poll');
        Route::get('/vouchers/{voucher}', [AccountantVoucherController::class, 'show'])->name('vouchers.show');
        Route::post('/vouchers/{voucher}/approve', [AccountantVoucherController::class, 'approve'])->name('vouchers.approve');
    });

    // Treasurer panel
    Route::middleware('role:treasurer')->prefix('treasurer')->name('treasurer.')->group(function () {
        Route::get('/dashboard', [TreasurerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [TreasurerAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/cheques', [TreasurerChequeController::class, 'index'])->name('cheques.index');
        Route::get('/cheques/export', [TreasurerChequeController::class, 'export'])->name('cheques.export');
        Route::get('/cheques/poll', [TreasurerChequeController::class, 'poll'])->name('cheques.poll');
        Route::get('/cheques/{voucher}', [TreasurerChequeController::class, 'show'])->name('cheques.show');
        Route::post('/cheques/{voucher}/acknowledge', [TreasurerChequeController::class, 'acknowledge'])->name('cheques.acknowledge');
        Route::post('/cheques/{voucher}/claim', [TreasurerChequeController::class, 'claim'])->name('cheques.claim');
    });

    // Internal Audit panel
    Route::middleware('role:internal_audit')->prefix('internal-audit')->name('internal-audit.')->group(function () {
        Route::get('/dashboard', [InternalAuditDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [InternalAuditAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/applications', [CodingReviewController::class, 'index'])->name('applications.index');
        Route::get('/applications/export', [CodingReviewController::class, 'export'])->name('applications.export');
        Route::get('/applications/poll', [CodingReviewController::class, 'poll'])->name('applications.poll');
        Route::get('/applications/{application}', [CodingReviewController::class, 'show'])->name('applications.show');
        Route::post('/applications/{application}/approve', [CodingReviewController::class, 'approve'])->name('applications.approve');
        Route::post('/applications/{application}/return', [CodingReviewController::class, 'return'])->name('applications.return');
    });

    // Budget Office panel
    Route::middleware('role:budget_officer')->prefix('budget-office')->name('budget-office.')->group(function () {
        Route::get('/dashboard', [BudgetOfficeDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [BudgetOfficeAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/vouchers', [BudgetOfficeVoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/export', [BudgetOfficeVoucherController::class, 'export'])->name('vouchers.export');
        Route::get('/vouchers/poll', [BudgetOfficeVoucherController::class, 'poll'])->name('vouchers.poll');
        Route::get('/vouchers/{application}', [BudgetOfficeVoucherController::class, 'show'])->name('vouchers.show');
        Route::post('/vouchers/{application}/approve', [BudgetOfficeVoucherController::class, 'approve'])->name('vouchers.approve');
        Route::post('/vouchers/{application}/hold', [BudgetOfficeVoucherController::class, 'hold'])->name('vouchers.hold');
        Route::post('/vouchers/{application}/release-hold', [BudgetOfficeVoucherController::class, 'releaseHold'])->name('vouchers.release-hold');
    });
});

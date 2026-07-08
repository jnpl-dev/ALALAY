<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\AssistanceCode;
use App\Models\AssistanceCodeReference;
use App\Models\AuditLog;
use App\Models\RequiredDocument;
use App\Models\SocialCaseStudy;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Voucher;
use App\Policies\ApplicationPolicy;
use App\Policies\AssistanceCategoryPolicy;
use App\Policies\AssistanceCodePolicy;
use App\Policies\AssistanceCodeReferencePolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\RequiredDocumentPolicy;
use App\Policies\SocialCaseStudyPolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\UserPolicy;
use App\Policies\VoucherPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Application::class => ApplicationPolicy::class,
        AssistanceCategory::class => AssistanceCategoryPolicy::class,
        AssistanceCode::class => AssistanceCodePolicy::class,
        AssistanceCodeReference::class => AssistanceCodeReferencePolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        RequiredDocument::class => RequiredDocumentPolicy::class,
        SocialCaseStudy::class => SocialCaseStudyPolicy::class,
        SystemSetting::class => SystemSettingPolicy::class,
        User::class => UserPolicy::class,
        Voucher::class => VoucherPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\AuditLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(function () {
            return inertia('Auth/Login');
        });

        Fortify::requestPasswordResetLinkView(function () {
            return inertia('Auth/ForgotPassword');
        });

        Fortify::resetPasswordView(function (Request $request) {
            return inertia('Auth/ResetPassword', [
                'token' => $request->token,
                'email' => $request->email,
            ]);
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey)->response(function (Request $request) {
                AuditLog::create([
                    'user_id' => null,
                    'role' => null,
                    'module' => 'auth',
                    'action' => 'login_lockout',
                    'description' => 'Login rate limited after 5 failed attempts for: ' . $request->input('email'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'created_at' => now(),
                ]);

                return back()->withErrors([
                    'email' => 'Too many login attempts. Please try again in 60 seconds.',
                ]);
            });
        });
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\SendContactMessageRequest;
use App\Mail\SendContactMail;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function send(SendContactMessageRequest $request)
    {
        if ($request->isBot()) {
            AuditLog::create([
                'user_id' => null,
                'role' => null,
                'module' => 'security',
                'action' => 'honeypot_triggered',
                'description' => 'Contact form honeypot triggered',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return back()->with('success', 'Your message has been sent. We will get back to you soon.');
        }

        $validated = $request->validated();

        Mail::to(config('mail.from.address'))
            ->send(new SendContactMail(
                $validated['name'],
                $validated['email'],
                $validated['message'],
            ));

        return back()->with('success', 'Your message has been sent. We will get back to you soon.');
    }
}

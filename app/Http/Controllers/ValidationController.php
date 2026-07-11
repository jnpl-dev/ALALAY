<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    public function checkBeneficiary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'beneficiary_first_name' => ['required', 'string', 'max:255'],
            'beneficiary_last_name' => ['required', 'string', 'max:255'],
            'beneficiary_middle_name' => ['nullable', 'string', 'max:255'],
            'beneficiary_name_extension' => ['nullable', 'string', 'max:10'],
        ]);

        $query = Application::where('beneficiary_first_name', $validated['beneficiary_first_name'])
            ->where('beneficiary_last_name', $validated['beneficiary_last_name']);

        if ($middleName = $validated['beneficiary_middle_name'] ?? null) {
            $query->where('beneficiary_middle_name', $middleName);
        }

        if ($nameExtension = $validated['beneficiary_name_extension'] ?? null) {
            $query->where('beneficiary_name_extension', $nameExtension);
        }

        $activeApp = (clone $query)
            ->whereNotIn('status', ['claimed'])
            ->select('id', 'reference_code', 'status', 'created_at')
            ->latest()
            ->first();

        if ($activeApp) {
            return response()->json([
                'eligible' => false,
                'reason' => 'active_application',
                'message' => 'This beneficiary already has an active application in progress.',
            ]);
        }

        $recentlyClaimed = (clone $query)
            ->where('status', 'claimed')
            ->where('claimed_at', '>=', now()->subMonths(3))
            ->select('id', 'reference_code', 'claimed_at')
            ->latest('claimed_at')
            ->first();

        if ($recentlyClaimed) {
            $cooldownUntil = $recentlyClaimed->claimed_at->addMonths(3)->format('M d, Y');
            return response()->json([
                'eligible' => false,
                'reason' => 'cooldown_period',
                'message' => "This beneficiary claimed assistance on {$recentlyClaimed->claimed_at->format('M d, Y')}. A new application can be submitted after {$cooldownUntil}.",
            ]);
        }

        return response()->json([
            'eligible' => true,
            'reason' => null,
            'message' => null,
        ]);
    }

    public function checkPhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:20'],
        ]);

        $phone = preg_replace('/[^0-9]/', '', $validated['value']);

        if (strlen($phone) !== 11) {
            return response()->json([
                'valid' => false,
                'message' => 'Phone number must be exactly 11 digits.',
            ]);
        }

        if (!str_starts_with($phone, '09')) {
            return response()->json([
                'valid' => false,
                'message' => 'Phone number must start with 09.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => null,
        ]);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'email', 'max:255'],
        ]);

        $query = User::where('email', $validated['value']);

        if ($excludeId = $request->query('exclude_id')) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            return response()->json([
                'valid' => false,
                'message' => 'This email is already taken.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => null,
        ]);
    }

    public function checkReferenceCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:20'],
        ]);

        $exists = Application::where('reference_code', $validated['value'])->exists();

        if (!$exists) {
            return response()->json([
                'valid' => false,
                'message' => 'No application found with this reference code.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => null,
        ]);
    }
}

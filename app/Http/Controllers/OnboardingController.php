<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function dismiss(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->forceFill([
            'onboarding_seen_at' => now(),
        ])->save();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}
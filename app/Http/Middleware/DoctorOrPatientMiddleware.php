<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DoctorOrPatientMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user || !$user->currentAccessToken()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Periksa apakah pengguna adalah Patient atau Doctor
        if ($role === 'doctor' && !$user instanceof \App\Models\Doctor) {
            return response()->json(['message' => 'Access denied for non-doctor'], 403);
        }

        if ($role === 'patient' && !$user instanceof \App\Models\Patient) {
            return response()->json(['message' => 'Access denied for non-patient'], 403);
        }

        return $next($request);
    }
}

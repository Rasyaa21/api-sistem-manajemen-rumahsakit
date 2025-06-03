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

        // Check user role matches the required role
        if ($user->role !== $role) {
            return response()->json([
                'message' => "Access denied. This endpoint requires {$role} role."
            ], 403);
        }

        return $next($request);
    }
}

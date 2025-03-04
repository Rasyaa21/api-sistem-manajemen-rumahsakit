<?php

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PatientController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|unique:patients',
            'password' => 'required|string|min:8',
        ]);

        $patient = Patient::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $patient->createToken('auth_token')->plainTextToken;

        return response()->json([
            'patient' => new PatientResource($patient),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $patient = Patient::where('email', $validated['email'])->first();

        if (!$patient || !Hash::check($validated['password'], $patient->password)) {
            return response()->json(['error' => 'wrong password'], 200);
        }

        $token = $patient->createToken('auth_token')->plainTextToken;

        return response()->json([
            'patient' => new PatientResource($patient),
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    public function completeProfile(Request $request)
    {
        $patient = Auth::user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'phone_number' => 'nullable|string|max:15',
            'medical_history' => 'nullable|string',
        ]);

        $patient->update($validated);

        return response()->json(new PatientResource($patient), 200);
    }

    public function currentUser()
    {
        return response()->json(new PatientResource(Auth::user()), 200);
    }
}

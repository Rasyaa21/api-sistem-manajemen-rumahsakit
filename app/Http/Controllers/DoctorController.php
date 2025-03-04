<?php

namespace App\Http\Controllers;

use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DoctorController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:doctors',
            'password' => 'required|string|min:8',
        ]);

        $doctor = Doctor::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $doctor->createToken('auth_token')->plainTextToken;

        return response()->json([
            'doctor' => new DoctorResource($doctor),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $doctor = Doctor::where('email', $validated['email'])->first();

        if (!$doctor || !Hash::check($validated['password'], $doctor->password)) {
            return response()->json([
                "error" => "wrong password"
            ], 200);
        }

        $token = $doctor->createToken('auth_token')->plainTextToken;

        return response()->json([
            'doctor' => new DoctorResource($doctor),
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    public function updateProfile(Request $request)
    {
        $doctor = Auth::user();

        $validated = $request->validate([
            'specialization' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:15',
            'practice_schedule' => 'nullable|string',
            'consultation_fee' => 'nullable|numeric',
        ]);

        $doctor->update($validated);

        return response()->json(new DoctorResource($doctor), 200);
    }

    public function currentUser()
    {
        return response()->json(new DoctorResource(Auth::user()), 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Exception;

class PatientController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                "name" => "required|string",
                'email' => 'required|string|email|unique:patients',
                'password' => 'required|string|min:8',
            ]);

            $patient = Patient::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $token = $patient->createToken('patient_token')->plainTextToken;

            return ResponseHelper::success([
                'patient' => new PatientResource($patient),
                'token' => $token,
            ], 'Registration successful', 201);
        } catch (Exception $e) {
            return ResponseHelper::error('Registration failed', 500, $e->getMessage());
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $patient = Patient::where('email', $validated['email'])->first();

            if (!$patient || !Hash::check($validated['password'], $patient->password)) {
                return ResponseHelper::error('Wrong password', 401);
            }

            $token = $patient->createToken('patient_token')->plainTextToken;

            return ResponseHelper::success([
                'patient' => new PatientResource($patient),
                'token' => $token,
            ], 'Login successful');
        } catch (Exception $e) {
            return ResponseHelper::error('Login failed', 500, $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            return ResponseHelper::success(null, 'Logged out successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Logout failed', 500, $e->getMessage());
        }
    }

    public function completeProfile(Request $request)
    {
        try {
            $patient = Patient::find(Auth::user()->id);

            if (!$patient) {
                return ResponseHelper::error('Unauthorized', 401);
            }

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date',
                'address' => 'nullable|string|max:500',
                'phone_number' => 'nullable|string|max:15',
                'medical_history' => 'nullable|string',
            ]);

            $patient->update($validated);

            return ResponseHelper::success(new PatientResource($patient), 'Profile updated successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Profile update failed', 500, $e->getMessage());
        }
    }

    public function currentUser()
    {
        try {
            $patient = Auth::user();

            if (!$patient) {
                return ResponseHelper::error('Unauthorized', 401);
            }

            return ResponseHelper::success(new PatientResource($patient), 'User data retrieved successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch user data', 500, $e->getMessage());
        }
    }
}

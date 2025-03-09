<?php

namespace App\Http\Controllers;

use App\Http\Resources\DoctorResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Log;

class DoctorController extends Controller
{
    public function register(Request $request)
    {
        try {
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

            $token = $doctor->createToken('doctor_token')->plainTextToken;

            return ResponseHelper::success([
                'doctor' => new DoctorResource($doctor),
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

            $doctor = Doctor::where('email', $validated['email'])->first();

            if (!$doctor || !Hash::check($validated['password'], $doctor->password)) {
                return ResponseHelper::error('Wrong password', 401);
            }

            $token = $doctor->createToken('doctor_token')->plainTextToken;

            return ResponseHelper::success([
                'doctor' => new DoctorResource($doctor),
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

    public function updateProfile(Request $request)
    {
        try {
            $doctor = Doctor::find(Auth::user()->id);

            if (!$doctor) {
                return ResponseHelper::error('Unauthorized', 401);
            }

            $validated = $request->validate([
                "password" => "sometimes|string|min:8",
                'specialization' => 'sometimes|string|max:255',
                'phone_number' => 'sometimes|string|max:15',
                'practice_schedule' => 'sometimes|string',
                'consultation_fee' => 'sometimes|numeric',
            ]);

            $doctor->update($validated);

            return ResponseHelper::success(new DoctorResource($doctor), 'Profile updated successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Profile update failed', 500, $e->getMessage());
        }
    }

    public function currentUser()
    {
        try {
            $doctor = Auth::user();
            if (!$doctor) {
                return ResponseHelper::error('Unauthorized', 401);
            }

            return ResponseHelper::success(new DoctorResource($doctor), 'User data retrieved successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch user data', 500, $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\DoctorResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Exception;

/**
 * @group Doctor Authentication & Profile
 *
 * APIs for doctor registration, login, logout, and profile management.
 */
class DoctorController extends Controller
{
    /**
     * Register a new doctor.
     *
     * @bodyParam name string required The doctor's name. Example: Dr. John Doe
     * @bodyParam email string required The doctor's email. Example: johndoe@example.com
     * @bodyParam password string required Password with at least 8 characters. Example: secret123
     *
     * @response 201 {
     *   "message": "Registration successful",
     *   "data": {
     *     "doctor": {
     *       "id": 1,
     *       "name": "Dr. John Doe",
     *       "email": "johndoe@example.com"
     *     },
     *     "token": "eyJ0eXAiOiJKV1QiLC..."
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Login a doctor and return token.
     *
     * @bodyParam email string required The doctor's email. Example: johndoe@example.com
     * @bodyParam password string required The doctor's password. Example: secret123
     *
     * @response 200 {
     *   "message": "Login successful",
     *   "data": {
     *     "doctor": {
     *       "id": 1,
     *       "name": "Dr. John Doe",
     *       "email": "johndoe@example.com"
     *     },
     *     "token": "eyJ0eXAiOiJKV1QiLC..."
     *   }
     * }
     * @response 401 {
     *   "message": "Wrong password"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Logout the authenticated doctor.
     *
     * @authenticated
     *
     * @response 200 {
     *   "message": "Logged out successfully"
     * }
     * @response 500 {
     *   "message": "Logout failed"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            return ResponseHelper::success(null, 'Logged out successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Logout failed', 500, $e->getMessage());
        }
    }

    /**
     * Update doctor's profile.
     *
     * @authenticated
     *
     * @bodyParam password string Optional. New password (min 8 characters). Example: newpassword123
     * @bodyParam specialization string Optional. Doctor's specialization. Example: Cardiologist
     * @bodyParam phone_number string Optional. Phone number (max 15 characters). Example: 08123456789
     * @bodyParam practice_schedule string Optional. Example: Monday - Friday 08:00 - 16:00
     * @bodyParam consultation_fee number Optional. Example: 150000
     *
     * @response 200 {
     *   "message": "Profile updated successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Dr. John Doe",
     *     ...
     *   }
     * }
     * @response 401 {
     *   "message": "Unauthorized"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Get current authenticated doctor data.
     *
     * @authenticated
     *
     * @response 200 {
     *   "message": "User data retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Dr. John Doe",
     *     "email": "johndoe@example.com"
     *   }
     * }
     * @response 401 {
     *   "message": "Unauthorized"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
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
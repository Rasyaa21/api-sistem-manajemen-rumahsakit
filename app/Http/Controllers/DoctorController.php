<?php

namespace App\Http\Controllers;

use App\Http\Resources\DoctorResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\DoctorApplicationResource;
use App\Http\Resources\ResponseHelper;
use App\Models\User;
use App\Models\Doctor;
use App\Models\DoctorApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="🔐 Authentication",
 *     description="Doctor application and login endpoints"
 * )
 *
 * @OA\Tag(
 *     name="👤 User Profile",
 *     description="Doctor profile management"
 * )
 */
class DoctorController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/doctor/register",
     *     operationId="doctorApplicationSubmit",
     *     tags={"🔐 Authentication"},
     *     summary="Apply to become a doctor",
     *     description="Submit an application to become a doctor (requires admin approval)",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Doctor application data",
     *         @OA\JsonContent(
     *             required={"full_name", "email", "password", "national_id", "license_number", "specialization"},
     *             @OA\Property(property="full_name", type="string", example="Dr. John Smith"),
     *             @OA\Property(property="email", type="string", format="email", example="dr.smith@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="phone_number", type="string", example="081234567890"),
     *             @OA\Property(property="national_id", type="string", example="1234567890123456"),
     *             @OA\Property(property="license_number", type="string", example="STR123456789"),
     *             @OA\Property(property="specialization", type="string", example="Cardiology"),
     *             @OA\Property(property="cv_url", type="string", example="https://example.com/cv.pdf"),
     *             @OA\Property(property="diploma_url", type="string", example="https://example.com/diploma.pdf")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Application submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Doctor application submitted successfully. Awaiting admin approval."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *                 @OA\Property(property="application", ref="#/components/schemas/DoctorApplicationResource"),
     *                 @OA\Property(property="token", type="string", example="2|laravel_sanctum_token")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|min:8',
                'phone_number' => 'nullable|string|max:20',
                'national_id' => 'required|string|unique:doctor_applications',
                'license_number' => 'required|string|unique:doctor_applications',
                'specialization' => 'required|string|max:255',
                'cv_url' => 'nullable|url',
                'diploma_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            // Create user account
            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'role' => 'patient', // Default role until approved
            ]);

            // Create doctor application
            $application = DoctorApplication::create([
                'user_id' => $user->id,
                'full_name' => $request->full_name,
                'national_id' => $request->national_id,
                'license_number' => $request->license_number,
                'specialization' => $request->specialization,
                'cv_url' => $request->cv_url,
                'diploma_url' => $request->diploma_url,
                'application_status' => 'pending',
            ]);

            $token = $user->createToken('user_token')->plainTextToken;

            return ResponseHelper::success([
                'user' => new UserResource($user),
                'application' => new DoctorApplicationResource($application),
                'token' => $token,
            ], 'Doctor application submitted successfully. Awaiting admin approval.', 201);
        } catch (Exception $e) {
            return ResponseHelper::error('Application submission failed', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/doctor/login",
     *     operationId="doctorLogin",
     *     tags={"🔐 Authentication"},
     *     summary="Log in as a doctor",
     *     description="Login with email and password (only for approved doctors)",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Doctor login credentials",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="dr.smith@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *                 @OA\Property(property="doctor", ref="#/components/schemas/DoctorResource"),
     *                 @OA\Property(property="token", type="string", example="2|laravel_sanctum_token")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Wrong credentials or not approved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials or account not approved as doctor")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return ResponseHelper::error('Invalid credentials', 401);
            }

            if ($user->role !== 'doctor') {
                return ResponseHelper::error('Account not approved as doctor', 401);
            }

            $doctor = $user->doctor;
            if (!$doctor) {
                return ResponseHelper::error('Doctor profile not found', 404);
            }

            $token = $user->createToken('doctor_token')->plainTextToken;

            return ResponseHelper::success([
                'user' => new UserResource($user),
                'doctor' => new DoctorResource($doctor),
                'token' => $token,
            ], 'Login successful');
        } catch (Exception $e) {
            return ResponseHelper::error('Login failed', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/doctor/logout",
     *     operationId="doctorLogout",
     *     tags={"👤 User Profile"},
     *     summary="Log out a doctor",
     *     description="Revoke the current authentication token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return ResponseHelper::success([], 'Logged out successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Logout failed', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/doctor/me",
     *     operationId="getCurrentDoctor",
     *     tags={"👤 User Profile"},
     *     summary="Get current doctor profile",
     *     description="Get the authenticated doctor's profile information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Doctor profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Doctor profile retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *                 @OA\Property(property="doctor", ref="#/components/schemas/DoctorResource")
     *             )
     *         )
     *     )
     * )
     */
    public function currentUser()
    {
        try {
            $user = Auth::user();
            $doctor = $user->doctor;

            return ResponseHelper::success([
                'user' => new UserResource($user),
                'doctor' => new DoctorResource($doctor),
            ], 'Doctor profile retrieved successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve doctor profile', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/doctor/profile",
     *     operationId="updateDoctorProfile",
     *     tags={"👤 User Profile"},
     *     summary="Update doctor profile",
     *     description="Update the authenticated doctor's profile information",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="full_name", type="string", example="Dr. John Smith"),
     *             @OA\Property(property="phone_number", type="string", example="081234567890"),
     *             @OA\Property(property="specialization", type="string", example="Cardiology"),
     *             @OA\Property(property="practice_schedule", type="string", example="Monday-Friday 08:00-17:00"),
     *             @OA\Property(property="consultation_fee", type="number", example=150000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *                 @OA\Property(property="doctor", ref="#/components/schemas/DoctorResource")
     *             )
     *         )
     *     )
     * )
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return ResponseHelper::error('Doctor profile not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|string|max:255',
                'phone_number' => 'sometimes|string|max:20',
                'specialization' => 'sometimes|string|max:255',
                'practice_schedule' => 'sometimes|string',
                'consultation_fee' => 'sometimes|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            // Update user data if provided
            $userData = $request->only(['full_name', 'phone_number']);
            if (!empty($userData)) {
                User::where('id', $user->id)->update($userData);
            }

            // Update doctor data if provided
            $doctorData = $request->only(['specialization', 'practice_schedule', 'consultation_fee']);
            if (!empty($doctorData)) {
                Doctor::where('id', $doctor->id)->update($doctorData);
            }

            // Refresh the models to get updated data
            $user = User::find($user->id);
            $doctor = Doctor::find($doctor->id);

            return ResponseHelper::success([
                'user' => new UserResource($user),
                'doctor' => new DoctorResource($doctor),
            ], 'Profile updated successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update profile', 500, $e->getMessage());
        }
    }
}
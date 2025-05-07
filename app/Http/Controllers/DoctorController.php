<?php

namespace App\Http\Controllers;

use App\Http\Resources\DoctorResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Doctor",
 *     description="API Endpoints for Doctor Management"
 * )
 */
class DoctorController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/doctor/register",
     *     operationId="doctorRegister",
     *     tags={"Doctor"},
     *     summary="Register a new doctor",
     *     description="Register a new doctor and return token",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Doctor registration data",
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *             @OA\Property(property="email", type="string", format="email", example="dr.smith@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="doctor", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                     @OA\Property(property="email", type="string", format="email", example="dr.smith@example.com")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="2|laravel_sanctum_token")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", example={"email": {"The email has already been taken."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Registration failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Registration failed"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/auth/doctor/login",
     *     operationId="doctorLogin",
     *     tags={"Doctor"},
     *     summary="Log in as a doctor",
     *     description="Login with email and password and receive authentication token",
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
     *                 @OA\Property(property="doctor", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                     @OA\Property(property="email", type="string", format="email", example="dr.smith@example.com")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="2|laravel_sanctum_token")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Wrong credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Wrong password"),
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Login failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Login failed"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/doctor/logout",
     *     operationId="doctorLogout",
     *     tags={"Doctor"},
     *     summary="Log out a doctor",
     *     description="Revoke the current authentication token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successfully"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Logout failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Logout failed"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/doctor/profile",
     *     operationId="updateDoctorProfile",
     *     tags={"Doctor"},
     *     summary="Update doctor profile information",
     *     description="Update authenticated doctor's profile information",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         description="Doctor profile data",
     *         @OA\JsonContent(
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="specialization", type="string", example="Cardiologist"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *             @OA\Property(property="practice_schedule", type="string", example="Monday - Friday 08:00 - 16:00"),
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
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                 @OA\Property(property="email", type="string", format="email", example="dr.smith@example.com"),
     *                 @OA\Property(property="specialization", type="string", example="Cardiologist"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="practice_schedule", type="string", example="Monday - Friday 08:00 - 16:00"),
     *                 @OA\Property(property="consultation_fee", type="number", example=150000)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Profile update failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Profile update failed"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/doctor/me",
     *     operationId="getDoctorProfile",
     *     tags={"Doctor"},
     *     summary="Get current doctor information",
     *     description="Get authenticated doctor's profile information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User data retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                 @OA\Property(property="email", type="string", format="email", example="dr.smith@example.com"),
     *                 @OA\Property(property="specialization", type="string", example="Cardiologist"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="practice_schedule", type="string", example="Monday - Friday 08:00 - 16:00"),
     *                 @OA\Property(property="consultation_fee", type="number", example=150000)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch user data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch user data"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
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

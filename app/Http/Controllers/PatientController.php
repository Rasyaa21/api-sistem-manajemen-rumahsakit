<?php

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Patient",
 *     description="API Endpoints for Patient Management"
 * )
 */
class PatientController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/patient/register",
     *     summary="Register a new patient",
     *     tags={"Patient"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Patient registration data",
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
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
     *                 @OA\Property(property="patient", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="2|laravel_sanctum_token")
     *             )
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

    /**
     * @OA\Post(
     *     path="/auth/patient/login",
     *     summary="Log in a patient",
     *     tags={"Patient"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Patient login credentials",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
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
     *                 @OA\Property(property="patient", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com")
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

    /**
     * @OA\Post(
     *     path="/patient/logout",
     *     summary="Log out a patient",
     *     tags={"Patient"},
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
     *     path="/patient/profile",
     *     summary="Update patient profile information",
     *     tags={"Patient"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Patient profile data",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="address", type="string", example="123 Main St, City"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *             @OA\Property(property="medical_history", type="string", example="No previous conditions")
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
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="address", type="string", example="123 Main St, City"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="medical_history", type="string", example="No previous conditions")
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

    /**
     * @OA\Get(
     *     path="/patient/me",
     *     summary="Get current patient information",
     *     tags={"Patient"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User data retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="address", type="string", example="123 Main St, City"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="medical_history", type="string", example="No previous conditions")
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

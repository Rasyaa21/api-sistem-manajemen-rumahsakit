<?php

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\ResponseHelper;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="🔐 Authentication",
 *     description="User registration and login endpoints"
 * )
 *
 * @OA\Tag(
 *     name="👤 User Profile",
 *     description="Patient profile management"
 * )
 */
class PatientController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/patient/register",
     *     operationId="patientRegister",
     *     tags={"🔐 Authentication"},
     *     summary="Register as a new patient",
     *     description="Register a new patient account and automatically create patient profile",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Patient registration data",
     *         @OA\JsonContent(
     *             required={"full_name", "email", "password"},
     *             @OA\Property(property="full_name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="phone_number", type="string", example="081234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *                 @OA\Property(property="patient", ref="#/components/schemas/PatientResource"),
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
                "full_name" => "required|string|max:255",
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|min:8',
                'phone_number' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'role' => 'patient',
            ]);

            // Auto-create patient profile
            $patient = Patient::create([
                'user_id' => $user->id,
            ]);

            $token = $user->createToken('patient_token')->plainTextToken;

            return ResponseHelper::success([
                'user' => new UserResource($user),
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
     *     operationId="patientLogin",
     *     tags={"🔐 Authentication"},
     *     summary="Log in as a patient",
     *     description="Login with email and password and receive authentication token",
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
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *                 @OA\Property(property="patient", ref="#/components/schemas/PatientResource"),
     *                 @OA\Property(property="token", type="string", example="2|laravel_sanctum_token")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Wrong credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
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

            // Auto-create patient profile if doesn't exist
            $patient = $user->patient;
            if (!$patient) {
                $patient = Patient::create([
                    'user_id' => $user->id,
                ]);
            }

            $token = $user->createToken('patient_token')->plainTextToken;

            return ResponseHelper::success([
                'user' => new UserResource($user),
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
     *     operationId="patientLogout",
     *     tags={"👤 User Profile"},
     *     summary="Log out a patient",
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
     *     path="/patient/me",
     *     operationId="getCurrentPatient",
     *     tags={"👤 User Profile"},
     *     summary="Get current patient profile",
     *     description="Get the authenticated patient's profile information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Patient profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Patient profile retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *                 @OA\Property(property="patient", ref="#/components/schemas/PatientResource")
     *             )
     *         )
     *     )
     * )
     */
    public function currentUser()
    {
        try {
            $user = Auth::user();
            $patient = $user->patient;

            // Auto-create patient profile if doesn't exist
            if (!$patient) {
                $patient = Patient::create([
                    'user_id' => $user->id,
                ]);
            }

            return ResponseHelper::success([
                'user' => new UserResource($user),
                'patient' => new PatientResource($patient),
            ], 'Patient profile retrieved successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve patient profile', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/patient/profile",
     *     operationId="completePatientProfile",
     *     tags={"👤 User Profile"},
     *     summary="Complete patient profile",
     *     description="Complete or update the authenticated patient's profile information",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="full_name", type="string", example="John Doe"),
     *             @OA\Property(property="phone_number", type="string", example="081234567890"),
     *             @OA\Property(property="national_id", type="string", example="1234567890123456"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="address", type="string", example="123 Main Street, City"),
     *             @OA\Property(property="blood_type", type="string", enum={"A", "B", "AB", "O", "A+", "B+", "AB+", "O+", "A-", "B-", "AB-", "O-"}, example="O+")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile completed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *                 @OA\Property(property="patient", ref="#/components/schemas/PatientResource")
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
    public function completeProfile(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|string|max:255',
                'phone_number' => 'sometimes|string|max:20',
                'national_id' => 'sometimes|string|unique:patients,national_id,' . ($user->patient->id ?? ''),
                'birth_date' => 'sometimes|date',
                'gender' => 'sometimes|in:male,female',
                'address' => 'sometimes|string',
                'blood_type' => 'sometimes|in:A,B,AB,O,A+,B+,AB+,O+,A-,B-,AB-,O-',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            // Update user data if provided
            $userData = $request->only(['full_name', 'phone_number']);
            if (!empty($userData)) {
                User::where('id', $user->id)->update($userData);
            }

            // Create or update patient profile
            $patientData = $request->only(['national_id', 'birth_date', 'gender', 'address', 'blood_type']);
            if (!empty($patientData)) {
                $patient = Patient::updateOrCreate(
                    ['user_id' => $user->id],
                    $patientData
                );
            } else {
                $patient = $user->patient;
                if (!$patient) {
                    $patient = Patient::create(['user_id' => $user->id]);
                }
            }

            // Refresh data
            $user = User::find($user->id);
            $patient = Patient::where('user_id', $user->id)->first();

            return ResponseHelper::success([
                'user' => new UserResource($user),
                'patient' => new PatientResource($patient),
            ], 'Profile completed successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to complete profile', 500, $e->getMessage());
        }
    }
}
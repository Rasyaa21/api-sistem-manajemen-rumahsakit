<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResponseHelper;
use App\Models\User;
use App\Models\Doctor;
use App\Models\DoctorApplication;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="API Endpoints for admin management"
 * )
 */
class AdminController extends Controller
{
    /**
     * @OA\Get(
     *     path="/admin/users",
     *     operationId="getAllUsers",
     *     tags={"Admin"},
     *     summary="Get all users",
     *     description="Retrieves all users in the system",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="users", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="full_name", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john@example.com"),
     *                         @OA\Property(property="phone_number", type="string", example="081234567890"),
     *                         @OA\Property(property="role", type="string", example="patient"),
     *                         @OA\Property(property="created_at", type="string", format="datetime")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized. Admin access required.")
     *         )
     *     )
     * )
     */
    public function getAllUsers()
    {
        try {
            $users = User::select('id', 'full_name', 'email', 'phone_number', 'role', 'created_at')->get();
            return ResponseHelper::success(['users' => $users], 'Users retrieved successfully', 200);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve users', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/admin/users/{id}/role",
     *     operationId="updateUserRole",
     *     tags={"Admin"},
     *     summary="Update user role",
     *     description="Updates a user's role in the system",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="role", type="string", enum={"admin", "doctor", "patient"}, example="doctor")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User role updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User role updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="role", type="string", example="doctor")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
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
    public function updateUserRole(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'required|in:admin,doctor,patient'
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $user = User::findOrFail($id);
            $user->role = $request->role;
            $user->save();

            return ResponseHelper::success(['user' => $user], 'User role updated successfully', 200);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update user role', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/doctor-applications",
     *     operationId="getDoctorApplications",
     *     tags={"Admin"},
     *     summary="Get all doctor applications",
     *     description="Retrieves all doctor applications with their status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Doctor applications retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Doctor applications retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="applications", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="full_name", type="string", example="Dr. John Doe"),
     *                         @OA\Property(property="national_id", type="string", example="1234567890123456"),
     *                         @OA\Property(property="license_number", type="string", example="STR123456"),
     *                         @OA\Property(property="specialization", type="string", example="Cardiology"),
     *                         @OA\Property(property="application_status", type="string", example="pending"),
     *                         @OA\Property(property="application_date", type="string", format="datetime"),
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="email", type="string", example="doctor@example.com"),
     *                             @OA\Property(property="phone_number", type="string", example="081234567890")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getDoctorApplications()
    {
        try {
            $applications = DoctorApplication::with('user:id,email,phone_number')->get();
            return ResponseHelper::success(['applications' => $applications], 'Doctor applications retrieved successfully', 200);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve doctor applications', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/admin/doctor-applications/{id}/approve",
     *     operationId="approveDoctorApplication",
     *     tags={"Admin"},
     *     summary="Approve doctor application",
     *     description="Approves a doctor application and creates doctor profile",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Doctor Application ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="admin_notes", type="string", example="Application approved. Welcome to our hospital."),
     *             @OA\Property(property="consultation_fee", type="number", example=150000, description="Doctor's consultation fee")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Doctor application approved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Doctor application approved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="application", type="object"),
     *                 @OA\Property(property="doctor", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Application already processed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Application has already been processed")
     *         )
     *     )
     * )
     */
    public function approveDoctorApplication(Request $request, $id)
    {
        try {
            $application = DoctorApplication::findOrFail($id);

            if ($application->application_status !== 'pending') {
                return ResponseHelper::error('Application has already been processed', 400);
            }

            // Update application status
            $application->application_status = 'approved';
            $application->admin_notes = $request->admin_notes ?? 'Application approved';
            $application->save();

            // Update user role to doctor
            $user = User::findOrFail($application->user_id);
            $user->role = 'doctor';
            $user->save();

            // Create doctor profile
            $doctor = Doctor::create([
                'user_id' => $application->user_id,
                'license_number' => $application->license_number,
                'specialization' => $application->specialization,
                'practice_schedule' => 'Monday-Friday 08:00-17:00', // Default schedule
                'consultation_fee' => $request->consultation_fee ?? 100000, // Default fee
            ]);

            return ResponseHelper::success([
                'application' => $application,
                'doctor' => $doctor
            ], 'Doctor application approved successfully', 200);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to approve doctor application', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/admin/doctor-applications/{id}/reject",
     *     operationId="rejectDoctorApplication",
     *     tags={"Admin"},
     *     summary="Reject doctor application",
     *     description="Rejects a doctor application with admin notes",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Doctor Application ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="admin_notes", type="string", example="License verification failed. Please resubmit with valid documents.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Doctor application rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Doctor application rejected successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="application", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Application already processed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Application has already been processed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Admin notes are required for rejection")
     *         )
     *     )
     * )
     */
    public function rejectDoctorApplication(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_notes' => 'required|string|min:10'
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Admin notes are required for rejection', 422, $validator->errors());
            }

            $application = DoctorApplication::findOrFail($id);

            if ($application->application_status !== 'pending') {
                return ResponseHelper::error('Application has already been processed', 400);
            }

            $application->application_status = 'rejected';
            $application->admin_notes = $request->admin_notes;
            $application->save();

            return ResponseHelper::success(['application' => $application], 'Doctor application rejected successfully', 200);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to reject doctor application', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/admin/users/{id}",
     *     operationId="deleteUser",
     *     tags={"Admin"},
     *     summary="Delete user",
     *     description="Deletes a user from the system",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return ResponseHelper::success([], 'User deleted successfully', 200);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to delete user', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/admin/login",
     *     operationId="adminLogin",
     *     tags={"Admin"},
     *     summary="Admin login",
     *     description="Authenticates an admin user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", example="admin@hospital.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="full_name", type="string", example="Admin User"),
     *                     @OA\Property(property="email", type="string", example="admin@hospital.com"),
     *                     @OA\Property(property="role", type="string", example="admin")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="1|abcdef123456...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Not an admin user",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Access denied. Admin role required.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return ResponseHelper::error('Invalid credentials', 401);
            }

            if ($user->role !== 'admin') {
                return ResponseHelper::error('Access denied. Admin role required.', 403);
            }

            $token = $user->createToken('admin-token')->plainTextToken;

            return ResponseHelper::success([
                'user' => $user,
                'token' => $token
            ], 'Login successful', 200);
        } catch (Exception $e) {
            return ResponseHelper::error('Login failed', 500, $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResponseHelper;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Registration;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="🏥 Patient Services",
 *     description="Patient registration and appointment management"
 * )
 *
 * @OA\Tag(
 *     name="👩‍⚕ Doctor Services",
 *     description="Doctor appointment and registration management"
 * )
 */
class ConsultationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/patient/doctors",
     *     operationId="getAllDoctors",
     *     tags={"🏥 Patient Services"},
     *     summary="Get all available doctors",
     *     description="Retrieves a list of all approved doctors for patients to select from",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Doctors data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Doctors retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(
     *                     property="doctors",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="user_id", type="integer", example=2),
     *                         @OA\Property(property="license_number", type="string", example="STR123456789"),
     *                         @OA\Property(property="specialization", type="string", example="Cardiology"),
     *                         @OA\Property(property="practice_schedule", type="string", example="Monday-Friday 08:00-17:00"),
     *                         @OA\Property(property="consultation_fee", type="number", example=150000),
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="full_name", type="string", example="Dr. John Smith"),
     *                             @OA\Property(property="email", type="string", example="dr.smith@example.com"),
     *                             @OA\Property(property="phone_number", type="string", example="081234567890")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve doctors",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve doctors")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $doctors = Doctor::with('user')->get();
            return ResponseHelper::success(['doctors' => $doctors], "Doctors retrieved successfully");
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve doctors', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/patient/registration",
     *     operationId="createRegistration",
     *     tags={"🏥 Patient Services"},
     *     summary="Register for a doctor visit",
     *     description="Creates a new registration for a patient to visit a doctor",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"doctor_id", "visit_date", "complaint"},
     *             @OA\Property(property="doctor_id", type="integer", example=1),
     *             @OA\Property(property="visit_date", type="string", format="date", example="2025-06-01"),
     *             @OA\Property(property="complaint", type="string", example="Chest pain and shortness of breath")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="registration", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="patient_id", type="integer", example=1),
     *                     @OA\Property(property="doctor_id", type="integer", example=2),
     *                     @OA\Property(property="visit_date", type="string", format="date", example="2025-06-01"),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="complaint", type="string", example="Chest pain and shortness of breath"),
     *                     @OA\Property(property="patient", type="object"),
     *                     @OA\Property(property="doctor", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Patient profile not completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Please complete your patient profile first")
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
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "doctor_id" => "required|exists:doctors,id",
                "visit_date" => "required|date|after_or_equal:today",
                "complaint" => "required|string|min:10",
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            $patient = $user->patient;

            if (!$patient) {
                return ResponseHelper::error('Please complete your patient profile first', 404);
            }

            $registration = Registration::create([
                "patient_id" => $patient->id,
                "doctor_id" => $request->doctor_id,
                "visit_date" => $request->visit_date,
                "complaint" => $request->complaint,
                "status" => "pending"
            ]);

            $registration = Registration::with(['patient.user', 'doctor.user'])->find($registration->id);

            return ResponseHelper::success(['registration' => $registration], "Registration created successfully", 201);
        } catch (Exception $e) {
            return ResponseHelper::error("Failed to create registration", 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/doctor/registrations",
     *     operationId="getDoctorRegistrations",
     *     tags={"🏥 Patient Services"},
     *     summary="Get all registrations for authenticated doctor",
     *     description="Retrieves all patient registrations assigned to the currently authenticated doctor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Registrations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registrations retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="registrations", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="patient_id", type="integer", example=1),
     *                         @OA\Property(property="doctor_id", type="integer", example=2),
     *                         @OA\Property(property="visit_date", type="string", format="date", example="2025-06-01"),
     *                         @OA\Property(property="status", type="string", example="pending"),
     *                         @OA\Property(property="complaint", type="string", example="Chest pain"),
     *                         @OA\Property(property="patient", type="object"),
     *                         @OA\Property(property="doctor", type="object")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Doctor profile not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Doctor profile not found")
     *         )
     *     )
     * )
     */
    public function getRegistrationsByDoctorId()
    {
        try {
            $user = Auth::user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return ResponseHelper::error('Doctor profile not found', 404);
            }

            $registrations = Registration::with(['patient.user', 'doctor.user'])
                ->where('doctor_id', $doctor->id)
                ->orderBy('visit_date', 'desc')
                ->get();

            return ResponseHelper::success(['registrations' => $registrations], "Registrations retrieved successfully");
        } catch (Exception $e) {
            return ResponseHelper::error("Failed to retrieve registrations", 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/patient/registrations",
     *     operationId="getPatientRegistrations",
     *     tags={"🏥 Patient Services"},
     *     summary="Get all registrations for authenticated patient",
     *     description="Retrieves all registrations made by the currently authenticated patient",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Registrations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registrations retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="registrations", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="patient_id", type="integer", example=1),
     *                         @OA\Property(property="doctor_id", type="integer", example=2),
     *                         @OA\Property(property="visit_date", type="string", format="date", example="2025-06-01"),
     *                         @OA\Property(property="status", type="string", example="confirmed"),
     *                         @OA\Property(property="complaint", type="string", example="Chest pain"),
     *                         @OA\Property(property="patient", type="object"),
     *                         @OA\Property(property="doctor", type="object")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Patient profile not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Patient profile not found")
     *         )
     *     )
     * )
     */
    public function getRegistrationsByPatientId()
    {
        try {
            $user = Auth::user();
            $patient = $user->patient;

            if (!$patient) {
                return ResponseHelper::error('Patient profile not found', 404);
            }

            $registrations = Registration::with(['patient.user', 'doctor.user'])
                ->where('patient_id', $patient->id)
                ->orderBy('visit_date', 'desc')
                ->get();

            return ResponseHelper::success(['registrations' => $registrations], "Registrations retrieved successfully");
        } catch (Exception $e) {
            return ResponseHelper::error("Failed to retrieve registrations", 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/doctor/registrations/{id}/status",
     *     operationId="updateRegistrationStatus",
     *     tags={"🏥 Patient Services"},
     *     summary="Update registration status",
     *     description="Updates the status of a patient registration (confirm, complete, cancel)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Registration ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"confirmed", "completed", "cancelled"}, example="confirmed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registration status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration status updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="registration", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to update this registration",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized to update this registration")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Registration not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Registration not found")
     *         )
     *     )
     * )
     */
    public function updateRegistrationStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:confirmed,completed,cancelled'
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return ResponseHelper::error('Doctor profile not found', 404);
            }

            $registration = Registration::with(['patient.user', 'doctor.user'])->find($id);

            if (!$registration) {
                return ResponseHelper::error('Registration not found', 404);
            }

            if ($registration->doctor_id !== $doctor->id) {
                return ResponseHelper::error('Unauthorized to update this registration', 403);
            }

            $registration->status = $request->status;
            $registration->save();

            return ResponseHelper::success(['registration' => $registration], "Registration status updated successfully");
        } catch (Exception $e) {
            return ResponseHelper::error("Failed to update registration status", 500, $e->getMessage());
        }
    }
}
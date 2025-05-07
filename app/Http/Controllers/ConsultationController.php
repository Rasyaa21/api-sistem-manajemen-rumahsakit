<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConsultationResource;
use App\Http\Resources\DoctorPatientResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Consultation;
use App\Models\Doctor;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Consultations",
 *     description="API Endpoints for managing consultations between patients and doctors"
 * )
 */
class ConsultationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/patient/doctor",
     *     operationId="getAllDoctors",
     *     tags={"Consultations"},
     *     summary="Get all available doctors",
     *     description="Retrieves a list of all doctors for patients to select from",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Doctors data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Doctor data retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Dr. John Smith"),
     *                     @OA\Property(property="email", type="string", example="dr.smith@example.com"),
     *                     @OA\Property(property="specialization", type="string", example="Cardiologist"),
     *                     @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                     @OA\Property(property="practice_schedule", type="string", example="Monday - Friday 08:00 - 16:00"),
     *                     @OA\Property(property="consultation_fee", type="number", example=50)
     *                 )
     *             )
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
     *         description="Failed to retrieve doctors",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieved doctor"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function index() {
        try {
            $doctors = Doctor::all();
            return ResponseHelper::success(DoctorPatientResource::collection($doctors), "Doctor data retrieved successfully");
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieved doctor', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/patient/consultation",
     *     operationId="createConsultation",
     *     tags={"Consultations"},
     *     summary="Book a new consultation",
     *     description="Creates a new consultation booking with a specified doctor",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id_doctor","consultation_date","consultation_time"},
     *             @OA\Property(property="id_doctor", type="integer", example=1),
     *             @OA\Property(property="consultation_date", type="string", format="date", example="2025-06-01"),
     *             @OA\Property(property="consultation_time", type="string", format="time", example="09:30")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Consultation created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success create consultation"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="consultation", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="id_doctor", type="integer", example=1),
     *                     @OA\Property(property="id_patient", type="integer", example=2),
     *                     @OA\Property(property="consultation_date", type="string", format="date", example="2025-06-01"),
     *                     @OA\Property(property="consultation_time", type="string", format="time", example="09:30"),
     *                     @OA\Property(property="doctor", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Dr. John Smith")
     *                     ),
     *                     @OA\Property(property="patient", type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Jane Doe")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Doctor is not available at this time slot",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Doctor is not available at this time slot"),
     *             @OA\Property(property="error", type="string", example="Doctor is not available at this time slot")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to create consultation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="failed to make consultation"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function store(Request $request) {
        try {
            $validated = $request->validate([
                "id_doctor" => "required|exists:doctors,id",
                "consultation_date" => "required|date",
                "consultation_time" => "required|date_format:H:i",
            ]);

            $consultationTime = strtotime($validated["consultation_date"] . ' ' . $validated["consultation_time"]);
            $oneHourBefore = date('Y-m-d H:i:s', $consultationTime - 3600);
            $oneHourAfter = date('Y-m-d H:i:s', $consultationTime + 3600);

            $existingConsultation = Consultation::where('id_doctor', $validated["id_doctor"])
                ->where('consultation_date', $validated["consultation_date"])
                ->whereRaw("TIME(consultation_time) BETWEEN TIME(?) AND TIME(?)", [$oneHourBefore, $oneHourAfter])
                ->first();

            if ($existingConsultation) {
                return ResponseHelper::error("Doctor is not available at this time slot", 422);
            }

            $consultation = Consultation::create([
                "id_doctor" => $validated["id_doctor"],
                "id_patient" => Auth::user()->id,
                "consultation_date" => $validated["consultation_date"],
                "consultation_time" => $validated["consultation_time"]
            ]);

            $consultation = Consultation::with('doctor', 'patient')->where('id', $consultation->id)->first();

            return ResponseHelper::success(["consultation" => new ConsultationResource($consultation)], "Success create consultation", 201);
        } catch (Exception $e) {
            return ResponseHelper::error("failed to make consultation", 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/doctor/consultations",
     *     operationId="getDoctorConsultations",
     *     tags={"Consultations"},
     *     summary="Get all consultations for authenticated doctor",
     *     description="Retrieves all consultations assigned to the currently authenticated doctor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Consultations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success get consultation"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="consultation", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="id_doctor", type="integer", example=1),
     *                         @OA\Property(property="id_patient", type="integer", example=2),
     *                         @OA\Property(property="consultation_date", type="string", format="date", example="2025-06-01"),
     *                         @OA\Property(property="consultation_time", type="string", format="time", example="09:30"),
     *                         @OA\Property(property="doctor", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Dr. John Smith")
     *                         ),
     *                         @OA\Property(property="patient", type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="name", type="string", example="Jane Doe")
     *                         )
     *                     )
     *                 )
     *             )
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
     *         description="Failed to retrieve consultations",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve consultation data"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function getConsultationBasedByDoctorId() {
        try {
            $doctorId = Auth::user()->id;
            $consultations = Consultation::where("id_doctor", $doctorId)->get();
            return ResponseHelper::success(["consultation" => ConsultationResource::collection($consultations)], "Success get consultation", 200);
        } catch (Exception $e) {
            return ResponseHelper::error("failed to retreive consultation data", 500, $e->getMessage());
        }
    }
}

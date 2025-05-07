<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicalRecordResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MedicalRecord;
use Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Medical Records",
 *     description="API Endpoints for managing patient medical records"
 * )
 */
/**
 * @group Medical Records
 *
 * APIs for managing medical records associated with consultations.
 */
class MedicalRecordController extends Controller
{
    /**
     * @OA\Get(
     *     path="/doctor/records",
     *     operationId="getAllMedicalRecords",
     *     tags={"Medical Records"},
     *     summary="Get all medical records",
     *     description="Retrieves all medical records created by the authenticated doctor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Medical records retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medical records retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(
     *                     property="medical_records",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="id_doctor", type="integer", example=1),
     *                         @OA\Property(property="id_patient", type="integer", example=2),
     *                         @OA\Property(property="medical_condition", type="string", example="Hypertension"),
     *                         @OA\Property(property="treatments", type="string", example="Prescribed medication for blood pressure"),
     *                         @OA\Property(property="notes", type="string", example="Patient should monitor blood pressure daily"),
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
     *         description="Failed to retrieve medical records",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve medical records"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $medicalRecords = MedicalRecord::with('doctor', 'patient')->where('id_doctor', Auth::user()->id)->get();
            return ResponseHelper::success(['medical_records' => MedicalRecordResource::collection($medicalRecords)], "Medical records retrieved successfully");
        } catch (Exception $e) {
            return ResponseHelper::error("Failed to retrieve medical records", 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/doctor/records",
     *     operationId="createMedicalRecord",
     *     tags={"Medical Records"},
     *     summary="Create a new medical record",
     *     description="Creates a new medical record for a patient",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id_patient", "medical_condition", "treatments"},
     *             @OA\Property(property="id_patient", type="integer", example=2),
     *             @OA\Property(property="medical_condition", type="string", example="Hypertension"),
     *             @OA\Property(property="treatments", type="string", example="Prescribed medication for blood pressure"),
     *             @OA\Property(property="notes", type="string", example="Patient should monitor blood pressure daily")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Medical record created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medical record created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="medical_record", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="id_doctor", type="integer", example=1),
     *                     @OA\Property(property="id_patient", type="integer", example=2),
     *                     @OA\Property(property="medical_condition", type="string", example="Hypertension"),
     *                     @OA\Property(property="treatments", type="string", example="Prescribed medication for blood pressure"),
     *                     @OA\Property(property="notes", type="string", example="Patient should monitor blood pressure daily"),
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
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", example={"id_patient": {"The id patient field is required."}})
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
     *         description="Failed to create medical record",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create medical record"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_patient' => 'required|exists:patients,id',
                'medical_condition' => 'required|string',
                'treatments' => 'required|string',
                'notes' => 'nullable|string',
            ]);

            $medicalRecord = MedicalRecord::create([
                'id_doctor' => Auth::user()->id,
                'id_patient' => $validated['id_patient'],
                'medical_condition' => $validated['medical_condition'],
                'treatments' => $validated['treatments'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $medicalRecord = MedicalRecord::with('doctor', 'patient')->where('id', $medicalRecord->id)->first();

            return ResponseHelper::success(['medical_record' => new MedicalRecordResource($medicalRecord)], "Medical record created successfully", 201);
        } catch (Exception $e) {
            return ResponseHelper::error("Failed to create medical record", 500, $e->getMessage());
        }
    }
}

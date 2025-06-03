<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicalRecordResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Registration;
use App\Models\Medicine;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordMedicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="👩‍⚕️ Doctor Services",
 *     description="Medical records management for doctors"
 * )
 *
 * @OA\Tag(
 *     name="🏥 Patient Services",
 *     description="Medical records access for patients"
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
     *     tags={"👩‍⚕️ Doctor Services"},
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
     *                         @OA\Property(property="registration_id", type="integer", example=1),
     *                         @OA\Property(property="diagnosis", type="string", example="Hypertension"),
     *                         @OA\Property(property="treatment", type="string", example="Lifestyle modification and medication"),
     *                         @OA\Property(property="additional_notes", type="string", example="Patient should monitor blood pressure daily"),
     *                         @OA\Property(property="input_date", type="string", format="datetime"),
     *                         @OA\Property(property="registration", type="object"),
     *                         @OA\Property(property="medicines", type="array", @OA\Items(type="object"))
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
    public function index()
    {
        try {
            $user = Auth::user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return ResponseHelper::error('Doctor profile not found', 404);
            }

            $medicalRecords = MedicalRecord::with([
                'registration.patient.user',
                'registration.doctor.user',
                'medicines'
            ])
            ->whereHas('registration', function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            })
            ->orderBy('input_date', 'desc')
            ->get();

            return ResponseHelper::success(['medical_records' => $medicalRecords], "Medical records retrieved successfully");
        } catch (Exception $e) {
            return ResponseHelper::error("Failed to retrieve medical records", 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/doctor/records",
     *     operationId="createMedicalRecord",
     *     tags={"👩‍⚕️ Doctor Services"},
     *     summary="Create a new medical record",
     *     description="Creates a new medical record for a patient registration with optional medicines",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"registration_id", "diagnosis", "treatment"},
     *             @OA\Property(property="registration_id", type="integer", example=1),
     *             @OA\Property(property="diagnosis", type="string", example="Hypertension Grade 1"),
     *             @OA\Property(property="treatment", type="string", example="Lifestyle modification and antihypertensive medication"),
     *             @OA\Property(property="additional_notes", type="string", example="Patient should monitor blood pressure daily and return in 2 weeks"),
     *             @OA\Property(
     *                 property="medicines",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="medicine_id", type="integer", example=1),
     *                     @OA\Property(property="quantity", type="integer", example=30),
     *                     @OA\Property(property="usage_instructions", type="string", example="Take 1 tablet daily after breakfast")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Medical record created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medical record created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="medical_record", type="object"),
     *                 @OA\Property(property="medicines", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to create record for this registration",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized to create medical record for this registration")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or insufficient medicine stock",
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
                'registration_id' => 'required|exists:registrations,id',
                'diagnosis' => 'required|string|min:5',
                'treatment' => 'required|string|min:10',
                'additional_notes' => 'nullable|string',
                'medicines' => 'sometimes|array',
                'medicines.*.medicine_id' => 'required_with:medicines|exists:medicines,id',
                'medicines.*.quantity' => 'required_with:medicines|integer|min:1',
                'medicines.*.usage_instructions' => 'required_with:medicines|string|min:5',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $user = Auth::user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return ResponseHelper::error('Doctor profile not found', 404);
            }

            // Verify that this registration belongs to the authenticated doctor
            $registration = Registration::find($request->registration_id);
            if ($registration->doctor_id !== $doctor->id) {
                return ResponseHelper::error('Unauthorized to create medical record for this registration', 403);
            }

            // Check if medical record already exists for this registration
            $existingRecord = MedicalRecord::where('registration_id', $request->registration_id)->first();
            if ($existingRecord) {
                return ResponseHelper::error('Medical record already exists for this registration', 400);
            }

            // Validate medicine stock if medicines are provided
            if ($request->has('medicines')) {
                foreach ($request->medicines as $medicineData) {
                    $medicine = Medicine::find($medicineData['medicine_id']);
                    if ($medicine->stock < $medicineData['quantity']) {
                        return ResponseHelper::error("Insufficient stock for medicine: {$medicine->medicine_name}. Available: {$medicine->stock}, Requested: {$medicineData['quantity']}", 422);
                    }
                }
            }

            // Create medical record
            $medicalRecord = MedicalRecord::create([
                'registration_id' => $request->registration_id,
                'diagnosis' => $request->diagnosis,
                'treatment' => $request->treatment,
                'additional_notes' => $request->additional_notes,
            ]);

            $assignedMedicines = [];

            // Add medicines if provided
            if ($request->has('medicines')) {
                foreach ($request->medicines as $medicineData) {
                    // Create medical record medicine relationship
                    $medicalRecordMedicine = MedicalRecordMedicine::create([
                        'medical_record_id' => $medicalRecord->id,
                        'medicine_id' => $medicineData['medicine_id'],
                        'quantity' => $medicineData['quantity'],
                        'usage_instructions' => $medicineData['usage_instructions'],
                    ]);

                    // Update medicine stock
                    $medicine = Medicine::find($medicineData['medicine_id']);
                    $medicine->stock -= $medicineData['quantity'];
                    $medicine->save();

                    $assignedMedicines[] = $medicalRecordMedicine->load('medicine');
                }
            }

            // Update registration status to completed
            $registration->status = 'completed';
            $registration->save();

            $medicalRecord = MedicalRecord::with([
                'registration.patient.user',
                'registration.doctor.user',
                'medicines'
            ])->find($medicalRecord->id);

            return ResponseHelper::success([
                'medical_record' => $medicalRecord,
                'medicines' => $assignedMedicines
            ], "Medical record created successfully", 201);
        } catch (Exception $e) {
            return ResponseHelper::error("Failed to create medical record", 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/doctor/records/{id}",
     *     operationId="getMedicalRecord",
     *     tags={"👩‍⚕️ Doctor Services"},
     *     summary="Get a specific medical record",
     *     description="Retrieves a specific medical record with all related information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Medical Record ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Medical record retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medical record retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="medical_record", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Medical record not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Medical record not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to access this medical record",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized to access this medical record")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return ResponseHelper::error('Doctor profile not found', 404);
            }

            $medicalRecord = MedicalRecord::with([
                'registration.patient.user',
                'registration.doctor.user',
                'medicines'
            ])->find($id);

            if (!$medicalRecord) {
                return ResponseHelper::error('Medical record not found', 404);
            }

            // Verify that this medical record belongs to the authenticated doctor
            if ($medicalRecord->registration->doctor_id !== $doctor->id) {
                return ResponseHelper::error('Unauthorized to access this medical record', 403);
            }

            return ResponseHelper::success(['medical_record' => $medicalRecord], "Medical record retrieved successfully");
        } catch (Exception $e) {
            return ResponseHelper::error("Failed to retrieve medical record", 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/patient/medical-records",
     *     operationId="getPatientMedicalRecords",
     *     tags={"🏥 Patient Services"},
     *     summary="Get patient's own medical records",
     *     description="Retrieves all medical records for the authenticated patient",
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
     *                     @OA\Items(ref="#/components/schemas/MedicalRecordResource")
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
    public function getPatientRecords()
    {
        try {
            $user = Auth::user();
            $patient = $user->patient;

            if (!$patient) {
                return ResponseHelper::error('Patient profile not found', 404);
            }

            $medicalRecords = MedicalRecord::with([
                'registration.doctor.user',
                'medicines'
            ])
            ->whereHas('registration', function ($query) use ($patient) {
                $query->where('patient_id', $patient->id);
            })
            ->orderBy('input_date', 'desc')
            ->get();

            return ResponseHelper::success([
                'medical_records' => MedicalRecordResource::collection($medicalRecords)
            ], 'Medical records retrieved successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve medical records', 500, $e->getMessage());
        }
    }
}

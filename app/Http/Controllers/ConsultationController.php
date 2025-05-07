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

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Consultation API",
 *     description="API untuk konsultasi antara pasien dan dokter"
 * )
 *
 * @OA\Tag(
 *     name="Consultations",
 *     description="API untuk mengelola konsultasi"
 * )
 */
class ConsultationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/consultations",
     *     tags={"Consultations"},
     *     summary="Mendapatkan semua data dokter",
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mendapatkan data dokter"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Gagal mendapatkan data dokter"
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
     *     path="/api/consultations",
     *     tags={"Consultations"},
     *     summary="Membuat konsultasi baru",
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
     *         description="Berhasil membuat konsultasi"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dokter tidak tersedia pada waktu ini"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Gagal membuat konsultasi"
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
     *     path="/api/consultations/doctor",
     *     tags={"Consultations"},
     *     summary="Mendapatkan konsultasi berdasarkan ID dokter (yang login)",
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mendapatkan data konsultasi"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Gagal mendapatkan data konsultasi"
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

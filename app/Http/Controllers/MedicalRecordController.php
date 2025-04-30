<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicalRecordResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MedicalRecord;
use Exception;

/**
 * @group Medical Records
 *
 * APIs for managing medical records associated with consultations.
 */
class MedicalRecordController extends Controller
{
    /**
     * Get all medical records created by the authenticated doctor.
     *
     * @authenticated
     *
     * @response 200 {
     *   "message": "success retreive medical record",
     *   "data": {
     *     "MedicalRecord": [
     *       {
     *         "id": 1,
     *         "id_consultation": 12,
     *         "diagnosis": "Flu",
     *         "treatment": "Rest and hydration",
     *         "examination_date": "2025-04-30"
     *       }
     *     ]
     *   }
     * }
     * @response 500 {
     *   "message": "failed to retreive medical record",
     *   "error": "Internal Server Error"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(){
        try{
            $records = MedicalRecord::whereHas('consultation', function($query) {
                $query->where('id_doctor', Auth::user()->id);
            })->get();
            return ResponseHelper::success(["MedicalRecord" => MedicalRecordResource::collection($records)], "success retreive medical record", 200);
        } catch (Exception $e){
            return ResponseHelper::error("failed to retreive medical record", 500, $e->getMessage());
        }
    }

    /**
     * Store a new medical record.
     *
     * @authenticated
     *
     * @bodyParam id_consultation integer required The ID of the consultation. Example: 12
     * @bodyParam diagnosis string required The diagnosis for the patient. Example: Flu
     * @bodyParam treatment string required The treatment given to the patient. Example: Rest and drink fluids
     * @bodyParam examination_date date required The date of examination. Example: 2025-04-30
     *
     * @response 201 {
     *   "message": "success created medical record",
     *   "data": {
     *     "MedicalRecord": {
     *       "id": 1,
     *       "id_consultation": 12,
     *       "diagnosis": "Flu",
     *       "treatment": "Rest and drink fluids",
     *       "examination_date": "2025-04-30"
     *     }
     *   }
     * }
     * @response 403 {
     *   "message": "unauthorized"
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "id_consultation": ["The id_consultation field is required."],
     *     ...
     *   }
     * }
     * @response 500 {
     *   "message": "failed to make medical record",
     *   "error": "Internal Server Error"
     * }
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request){
        try{
            $validated = $request->validate([
                "id_consultation" => "required|exists:consultations,id",
                "diagnosis" => "required|string",
                "treatment" => "required|string",
                "examination_date" => "required|date"
            ]);

            $consultations = Consultation::where("id_doctor", Auth::user()->id)->pluck('id');
            if(!$consultations->contains($validated['id_consultation'])){
                return ResponseHelper::error('unauthorized', 403);
            }

            $medicalRecord = MedicalRecord::create([
                'id_consultation' => $validated['id_consultation'],
                'diagnosis' => $validated['diagnosis'],
                'treatment' => $validated['treatment'],
                'examination_date' => $validated['examination_date']
            ]);

            return ResponseHelper::success(["MedicalRecord" => new MedicalRecordResource($medicalRecord)], "success created medical record", 201);

        } catch (Exception $e) {
            return ResponseHelper::error("failed to make medical record", 500, $e->getMessage());
        }
    }
}
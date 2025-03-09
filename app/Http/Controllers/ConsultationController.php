<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConsultationResource;
use App\Http\Resources\DoctorPatientResource;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Consultation;
use App\Models\Doctor;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function index(){
        try{
            $doctors = Doctor::all();
            return ResponseHelper::success(DoctorPatientResource::collection($doctors), "Doctor data retrieved successfully");
        } catch (Exception $e){
            return ResponseHelper::error('Failed to retrieved doctor', 500, $e->getMessage());
        }
    }

    public function store(Request $request){
        try{
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
        } catch (Exception $e){
            return ResponseHelper::error("failed to make consultation", 500, $e->getMessage());
        }
    }

    public function getConsultationBasedByDoctorId(){
        try{
            $doctorId = Auth::user()->id;
            $consultations = Consultation::where("id_doctor", $doctorId)->get();
            return ResponseHelper::success(["consultation" => ConsultationResource::collection($consultations)], "Success create consultation", 200);
        } catch (Exception $e){
            return ResponseHelper::error("failed to retreive consultation data", 500, $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicalRecordResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MedicalRecord;
use Exception;
use GuzzleHttp\Psr7\Response;

class MedicalRecordController extends Controller
{
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

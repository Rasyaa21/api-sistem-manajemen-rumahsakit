<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReportResource;
use App\Http\Resources\ResponseHelper;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Report;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function makeReport(){
        try{
            $doctorId = Auth::user()->id;

            $existingReport = Report::where('id_doctor', $doctorId)
                ->whereDate('report_date', Carbon::today())
                ->first();

            if ($existingReport) {
                throw new Exception("Report for today already exists");
            }

            $patientCount = Consultation::where('id_doctor', $doctorId)
                ->whereDate('consultation_date', Carbon::today())->count();
            $doctor = Doctor::findOrFail($doctorId);
            Log::info($doctor);
            $income = $patientCount * $doctor->consultation_fee;

            $report = Report::create([
                'id_doctor'    => $doctorId,
                'patient_count' => $patientCount,
                'income'       => $income,
                'report_date'  => Carbon::today(),
            ]);
            return ResponseHelper::success(["report" => new ReportResource($report)], "success make report", 201);
        } catch (Exception $e){
            return ResponseHelper::error("failed to make consultation", 500, $e->getMessage());
        }
    }

    public function index(){
        try{
            $reports = Report::where('id_doctor', Auth::user()->id)->get();
            return ResponseHelper::success(['reports' => ReportResource::collection($reports)], "success retreive the report", 200);
        } catch (Exception $e){
            return ResponseHelper::error("failed to make consultation", 500, $e->getMessage());
        }
    }
}

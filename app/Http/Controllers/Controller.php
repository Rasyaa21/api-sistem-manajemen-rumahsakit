<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Hospital Management System API",
 *     version="1.0.0",
 *     description="API for managing hospital operations with role-based access control",
 *     @OA\Contact(
 *         email="admin@hospital.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Local development server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(
 *     name="🔐 Authentication",
 *     description="User registration and login endpoints for all roles"
 * )
 *
 * @OA\Tag(
 *     name="👤 User Profile",
 *     description="User profile management - accessible to patients and doctors"
 * )
 *
 * @OA\Tag(
 *     name="👩‍⚕️ Doctor Services",
 *     description="Doctor-specific functionalities (requires doctor role)"
 * )
 *
 * @OA\Tag(
 *     name="🏥 Patient Services",
 *     description="Patient-specific functionalities (requires patient role)"
 * )
 *
 * @OA\Tag(
 *     name="⚙️ Admin Management",
 *     description="Administrative functions (requires admin role)"
 * )
 *
 * @OA\Tag(
 *     name="📄 File Upload",
 *     description="File upload services for documents"
 * )
 *
 * @OA\Schema(
 *     schema="UserResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="full_name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", example="john@example.com"),
 *     @OA\Property(property="phone_number", type="string", example="081234567890"),
 *     @OA\Property(property="role", type="string", enum={"admin", "doctor", "patient"}, example="patient"),
 *     @OA\Property(property="created_at", type="string", format="datetime"),
 *     @OA\Property(property="updated_at", type="string", format="datetime")
 * )
 *
 * @OA\Schema(
 *     schema="PatientResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="national_id", type="string", example="1234567890123456"),
 *     @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
 *     @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
 *     @OA\Property(property="address", type="string", example="123 Main Street"),
 *     @OA\Property(property="blood_type", type="string", example="O+"),
 *     @OA\Property(property="created_at", type="string", format="datetime"),
 *     @OA\Property(property="updated_at", type="string", format="datetime")
 * )
 *
 * @OA\Schema(
 *     schema="DoctorResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="license_number", type="string", example="STR123456789"),
 *     @OA\Property(property="specialization", type="string", example="Cardiology"),
 *     @OA\Property(property="practice_schedule", type="string", example="Monday-Friday 08:00-17:00"),
 *     @OA\Property(property="consultation_fee", type="number", example=150000),
 *     @OA\Property(property="created_at", type="string", format="datetime"),
 *     @OA\Property(property="updated_at", type="string", format="datetime")
 * )
 *
 * @OA\Schema(
 *     schema="DoctorApplicationResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="full_name", type="string", example="Dr. John Smith"),
 *     @OA\Property(property="national_id", type="string", example="1234567890123456"),
 *     @OA\Property(property="license_number", type="string", example="STR123456789"),
 *     @OA\Property(property="specialization", type="string", example="Cardiology"),
 *     @OA\Property(property="application_status", type="string", enum={"pending", "approved", "rejected"}, example="pending"),
 *     @OA\Property(property="created_at", type="string", format="datetime"),
 *     @OA\Property(property="updated_at", type="string", format="datetime")
 * )
 *
 * @OA\Schema(
 *     schema="RegistrationResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="patient_id", type="integer", example=1),
 *     @OA\Property(property="doctor_id", type="integer", example=1),
 *     @OA\Property(property="visit_date", type="string", format="date", example="2025-01-15"),
 *     @OA\Property(property="status", type="string", enum={"pending", "confirmed", "completed", "cancelled"}, example="pending"),
 *     @OA\Property(property="complaint", type="string", example="Chest pain"),
 *     @OA\Property(property="created_at", type="string", format="datetime"),
 *     @OA\Property(property="updated_at", type="string", format="datetime")
 * )
 *
 * @OA\Schema(
 *     schema="MedicalRecordResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="registration_id", type="integer", example=1),
 *     @OA\Property(property="diagnosis", type="string", example="Hypertension Grade 1"),
 *     @OA\Property(property="treatment", type="string", example="Lifestyle modification and medication"),
 *     @OA\Property(property="additional_notes", type="string", example="Patient should monitor blood pressure daily"),
 *     @OA\Property(property="created_at", type="string", format="datetime"),
 *     @OA\Property(property="updated_at", type="string", format="datetime")
 * )
 *
 * @OA\Schema(
 *     schema="MedicineResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="medicine_name", type="string", example="Amlodipine"),
 *     @OA\Property(property="medicine_type", type="string", example="tablet"),
 *     @OA\Property(property="dosage", type="string", example="5mg"),
 *     @OA\Property(property="unit", type="string", example="tablet"),
 *     @OA\Property(property="stock", type="integer", example=100),
 *     @OA\Property(property="created_at", type="string", format="datetime"),
 *     @OA\Property(property="updated_at", type="string", format="datetime")
 * )
 */
class Controller
{

}

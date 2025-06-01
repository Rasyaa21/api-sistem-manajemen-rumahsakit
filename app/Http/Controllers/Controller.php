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
 *     description="API for managing hospital operations including patients, doctors, registrations, medical records, and reports",
 *     @OA\Contact(
 *         email="admin@hospital.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Hospital Management API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="bearer",
 *     description="Enter token in format (Bearer <token>)"
 * )
 */
class Controller
{

}

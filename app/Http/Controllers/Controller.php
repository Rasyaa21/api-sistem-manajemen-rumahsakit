<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * @OA\Info(
 *     title="Hospital Management API",
 *     version="1.0.0",
 *     description="API for Hospital Management System",
 *     @OA\Contact(
 *         email="admin@hospital.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 */

abstract class Controller
{
    //
}

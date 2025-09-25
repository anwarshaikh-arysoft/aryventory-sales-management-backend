<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Aryventory Sales Management API",
 *     version="1.0.0",
 *     description="API for managing sales executives, leads, meetings, shifts, and business operations",
 *     @OA\Contact(
 *         email="support@aryventory.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Development server"
 * )
 * 
 * @OA\Server(
 *     url="https://api.aryventory.com/api",
 *     description="Production server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format (Bearer <token>)"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization"
 * )
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="User management operations"
 * )
 * 
 * @OA\Tag(
 *     name="Leads",
 *     description="Lead management operations"
 * )
 * 
 * @OA\Tag(
 *     name="Meetings",
 *     description="Meeting management operations"
 * )
 * 
 * @OA\Tag(
 *     name="Shifts",
 *     description="Shift and break management operations"
 * )
 * 
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard and analytics operations"
 * )
 * 
 * @OA\Tag(
 *     name="Settings",
 *     description="System settings and configuration"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
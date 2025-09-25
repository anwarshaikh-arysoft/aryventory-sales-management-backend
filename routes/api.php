<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\SalesExecutiveLeadController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\UserPreferencesController;
use App\Http\Controllers\Api\LeadStatusController;
use App\Http\Controllers\Api\DashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

// GET|HEAD        api/roles ................................................... roles.index › Api\RoleController@index
// POST            api/roles ................................................... roles.store › Api\RoleController@store
// GET|HEAD        api/roles/{role} .............................................. roles.show › Api\RoleController@show
// PUT|PATCH       api/roles/{role} .......................................... roles.update › Api\RoleController@update
// DELETE          api/roles/{role} ........................................ roles.destroy › Api\RoleController@destroy

/**
 * @OA\Post(
 *     path="/api/login",
 *     summary="User login",
 *     description="Authenticate user and return access token",
 *     operationId="login",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User email address"),
 *             @OA\Property(property="password", type="string", example="password123", description="User password")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="token", type="string", example="1|abcdef123456789", description="Access token"),
 *             @OA\Property(property="user", type="object", ref="#/components/schemas/User", description="User information")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Invalid credentials",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Invalid credentials")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation failed",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Validation failed"),
 *             @OA\Property(property="errors", type="object", example={"email": {"The email field is required."}})
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unable to login"),
 *             @OA\Property(property="error", type="string", example="Internal server error")
 *         )
 *     )
 * )
 */
// Login with sanctum Token
Route::post('/login', function (Request $request) {
    try {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Auth::attempt($validator->validated())) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Unable to login',
            'error' => $e->getMessage(),
        ], 500);
    }
});

/**
 * @OA\Get(
 *     path="/api/user",
 *     summary="Get current user",
 *     description="Get information about the currently authenticated user",
 *     operationId="getCurrentUser",
 *     tags={"Authentication"},
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="User information retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="John Doe"),
 *             @OA\Property(property="email", type="string", example="john@example.com"),
 *             @OA\Property(property="phone", type="string", example="9876543210"),
 *             @OA\Property(property="designation", type="string", example="Sales Executive"),
 *             @OA\Property(property="role_id", type="integer", example=2),
 *             @OA\Property(property="group_id", type="integer", example=1),
 *             @OA\Property(property="manager_id", type="integer", example=3, nullable=true),
 *             @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-01-01T10:00:00Z", nullable=true),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T10:00:00Z")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     )
 * )
 */
// Get current logged in user
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('user-stats', StatsController::class)->only(['index']);
});

// Shift APIs
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/shift/history', [ShiftController::class, 'showShifts']);
    Route::post('/shift/start', [ShiftController::class, 'startShift']);
    Route::post('/shift/start-break', [ShiftController::class, 'startBreak']);
    Route::post('/shift/end-break', [ShiftController::class, 'endBreak']);
    Route::post('/shift/end', [ShiftController::class, 'endShift']);
    Route::get('/shift/status', [ShiftController::class, 'getShiftStatus']);
    Route::get('/shift/breaks', [ShiftController::class, 'getBreaks']); // Get all breaks for current shift
    Route::get('/shift/break-history', [ShiftController::class, 'getBreakHistory']); // Get break history
    Route::put('/shift/breaks/{breakId}', [ShiftController::class, 'updateBreak']); // Update break details
    Route::delete('/shift/breaks/{breakId}', [ShiftController::class, 'deleteBreak']); // Delete a break
    Route::get('/shift/user-shifts', [ShiftController::class, 'getUserShifts']); // For managers/admins
    Route::get('/shift/all-users-shifts', [ShiftController::class, 'getAllUsersShifts']); // For admin overview
    Route::get('/shift/current-status', [ShiftController::class, 'getAllUsersCurrentStatus']); // Current status of all users
    Route::get('/users/{user}/shifts', [ShiftController::class, 'getUserDetailedShifts']); // Detailed shift information with meetings
});



// Leads api for sales executives
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/sales-executive/leads', SalesExecutiveLeadController::class);
    Route::get('/sales-executive/leads', [SalesExecutiveLeadController::class, 'leads']);
    Route::get('/sales-executive/leads-by-follow-up', [SalesExecutiveLeadController::class, 'leadsByFollowUpDate']);

    // Lead Counts by status for sales executives
    Route::get('/sales-executive/lead-counts-by-status', [SalesExecutiveLeadController::class, 'leadCountsByStatus']);
});

// User preferences
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user-preferences', [UserPreferencesController::class, 'index']);
    Route::put('/user-preferences/update', [UserPreferencesController::class, 'update']);
});

Route::middleware('auth:api')->get('/me', function (Request $request) {
    return response()->json([
        'user' => $request->user(),
        'role' => $request->user()?->role
    ]);
});

// Roles crud
Route::apiResource('roles', RoleController::class);

// Groups crud
Route::apiResource('groups', GroupController::class);

// Users crud
Route::apiResource('users', App\Http\Controllers\Api\UserController::class);
Route::get('users-managers', [App\Http\Controllers\Api\UserController::class, 'getManagers']);
Route::put('users/{user}/password', [App\Http\Controllers\Api\UserController::class, 'updatePassword']);

// Leads crud
Route::apiResource('leads', App\Http\Controllers\Api\LeadController::class);
Route::delete('leads-bulk', [App\Http\Controllers\Api\LeadController::class, 'bulkDestroy']);
Route::get('leads-export', [App\Http\Controllers\Api\LeadController::class, 'export']);

// Meetings crud
Route::middleware('auth:sanctum')->group(function(){
    Route::apiResource('meetings', App\Http\Controllers\Api\MeetingController::class);
    Route::post('/meetings/check/status', [App\Http\Controllers\Api\MeetingController::class, 'getMeetingStatus']);

    Route::post('/meetings/start', [App\Http\Controllers\Api\MeetingController::class, 'startMeeting']);
    Route::post('/meetings/end', [App\Http\Controllers\Api\MeetingController::class, 'endMeeting']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Public (for all authenticated users)
    Route::get('lead-statuses', [LeadStatusController::class, 'index']);
    Route::get('lead-statuses/{lead_status}', [LeadStatusController::class, 'show']);

    // Admin-only
    Route::middleware('role:Admin')->group(function () {
        Route::post('lead-statuses', [LeadStatusController::class, 'store']);
        Route::put('lead-statuses/{lead_status}', [LeadStatusController::class, 'update']);
        Route::patch('lead-statuses/{lead_status}', [LeadStatusController::class, 'update']);
        Route::delete('lead-statuses/{lead_status}', [LeadStatusController::class, 'destroy']);
    });
});

Route::apiResource('recorded-audios-meeting', App\Http\Controllers\Api\RecordedAudioForMeetingController::class);
Route::apiResource('selfies-meeting', App\Http\Controllers\Api\SelfieForMeetingController::class);
Route::apiResource('shop-photos-meeting', App\Http\Controllers\Api\ShopPhotoForMeetingController::class);

Route::get('leads-form-options', [App\Http\Controllers\Api\LeadController::class, 'getFormOptions']);
Route::get('leads-count', [App\Http\Controllers\Api\LeadController::class, 'getLeadsCount']);

// Dashboard APIs
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/dashboard/shift-locations', [DashboardController::class, 'shiftLocations']);
    Route::get('/dashboard/revenue-breakdown', [DashboardController::class, 'revenueBreakdown']);
    Route::get('/dashboard/user-performance', [DashboardController::class, 'userPerformance']);
});

Route::get('dashboard', function () {
    return response()->json(['message' => 'Welcome Admin or Manager']);
});

Route::middleware(['auth:api', 'role:Admin,Manager'])->group(function () {
    Route::get('/dashboard', function () {
        return response()->json(['message' => 'Welcome Admin or Manager']);
    });
});

// Admin Settings CRUD - Protected routes
Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::apiResource('business-types', App\Http\Controllers\Api\BusinessTypeController::class);
    Route::apiResource('current-systems', App\Http\Controllers\Api\CurrentSystemController::class);
    Route::apiResource('plans', App\Http\Controllers\Api\PlanController::class);
    Route::apiResource('preferences', App\Http\Controllers\Api\PreferenceController::class);
    Route::apiResource('targets', App\Http\Controllers\Api\TargetController::class);
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('business-types', App\Http\Controllers\Api\BusinessTypeController::class)->only(['index', 'show']);
    Route::apiResource('current-systems', App\Http\Controllers\Api\CurrentSystemController::class)->only(['index', 'show']);
    Route::apiResource('plans', App\Http\Controllers\Api\PlanController::class)->only(['index', 'show']);
    Route::apiResource('preferences', App\Http\Controllers\Api\PreferenceController::class)->only(['index', 'show']);
    Route::apiResource('targets', App\Http\Controllers\Api\TargetController::class)->only(['index', 'show']);
});
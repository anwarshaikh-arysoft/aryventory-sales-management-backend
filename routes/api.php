<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\SalesExecutiveLeadController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\UserPreferencesController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

// GET|HEAD        api/roles ................................................... roles.index › Api\RoleController@index
// POST            api/roles ................................................... roles.store › Api\RoleController@store
// GET|HEAD        api/roles/{role} .............................................. roles.show › Api\RoleController@show
// PUT|PATCH       api/roles/{role} .......................................... roles.update › Api\RoleController@update
// DELETE          api/roles/{role} ........................................ roles.destroy › Api\RoleController@destroy

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
});

// Leads api for sales executives
Route::middleware('auth:sanctum')->group(function () {
    // Route::apiResource('/sales-executive/leads', SalesExecutiveLeadController::class);
    Route::get('/sales-executive/leads', [SalesExecutiveLeadController::class, 'leads']);
    Route::get('/sales-executive/leads-by-follow-up', [SalesExecutiveLeadController::class, 'leadsByFollowUpDate']);
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

// Leads crud
Route::apiResource('leads', App\Http\Controllers\Api\LeadController::class);

Route::apiResource('meetings', App\Http\Controllers\Api\MeetingController::class);

Route::apiResource('recorded-audios-meeting', App\Http\Controllers\Api\RecordedAudioForMeetingController::class);
Route::apiResource('selfies-meeting', App\Http\Controllers\Api\SelfieForMeetingController::class);
Route::apiResource('shop-photos-meeting', App\Http\Controllers\Api\ShopPhotoForMeetingController::class);

Route::get('leads-form-options', [App\Http\Controllers\Api\LeadController::class, 'getFormOptions']);
Route::get('leads-count', [App\Http\Controllers\Api\LeadController::class, 'getLeadsCount']);

Route::get('dashboard', function () {
    return response()->json(['message' => 'Welcome Admin or Manager']);
});

Route::middleware(['auth:api', 'role:Admin,Manager'])->group(function () {
    Route::get('/dashboard', function () {
        return response()->json(['message' => 'Welcome Admin or Manager']);
    });
});

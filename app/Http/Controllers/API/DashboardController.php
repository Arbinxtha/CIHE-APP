<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends DM_Basecontroller
{
    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     summary="Fetch dashboard data based on user role (admin or lecturer)",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", example="Admin dashboard data fetched successfully."),
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="total_student", type="integer", example=200),
     *                         @OA\Property(property="total_teacher", type="integer", example=20),
     *                         @OA\Property(property="total_course", type="integer", example=30),
     *                         @OA\Property(property="total_active_user", type="integer", example=180)
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", example="Lecturer dashboard data fetched successfully."),
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="total_students", type="integer", example=50)
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden access for user role",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Role not permitted to access dashboard.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to fetch dashboard data."),
     *             @OA\Property(property="error", type="string", example="Exception message here")
     *         )
     *     )
     * )
     */

    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }

            $userId = $user->id;
            $data = [];

            if ($user->role === 'admin') {
                $data['total_student'] = User::where('role', 'student')->count();
                $data['total_teacher'] = User::where('role', 'lecturer')->count();
                $data['total_course'] = Course::count();
                $data['total_active_user'] = User::where('status', 'active')->count();
                parent::saveLog('Admin viewed the dashboard');

                return response()->json([
                    'message' => 'Admin dashboard data fetched successfully.',
                    'data' => $data
                ], 200);
            }

            if ($user->role === 'lecturer') {
                $groupIds = GroupMember::where('user_id', $userId)->pluck('group_id');

                $studentIds = GroupMember::whereIn('group_id', $groupIds)
                    ->where('user_id', '!=', $userId)
                    ->pluck('user_id');

                $data['total_students'] = User::whereIn('id', $studentIds)
                    ->where('role', 'student')
                    ->count();

                parent::saveLog('User viewed the dashboard');

                return response()->json([
                    'message' => 'Lecturer dashboard data fetched successfully.',
                    'data' => $data
                ], 200);
            }

            return response()->json([
                'message' => 'Role not permitted to access dashboard.'
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch dashboard data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/getstudents",
     *     summary="Fetch students under lecturer's group",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of students successfully retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Students retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(
     *                     property="students",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="role", type="string", example="student"),
     *                         @OA\Property(property="username", type="string", example="john_doe")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden for non-lecturer roles",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Only lecturers can access this resource.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to fetch students."),
     *             @OA\Property(property="error", type="string", example="Exception message here...")
     *         )
     *     )
     * )
     */

    public function getStudents()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }

            if ($user->role !== 'lecturer') {
                return response()->json([
                    'message' => 'Only lecturers can access this resource.',
                ], 403);
            }

            $userId = $user->id;
            $groupIds = GroupMember::where('user_id', $userId)->pluck('group_id');

            $studentIds = GroupMember::whereIn('group_id', $groupIds)
                ->where('user_id', '!=', $userId)
                ->pluck('user_id');

            $students = User::whereIn('id', $studentIds)
                ->where('role', 'student')
                ->get(['id', 'role', 'username']);

            if ($students->isEmpty()) {
                return response()->json([
                    'message' => 'No students found in lecturer\'s group(s).',
                    'data' => []
                ], 200);
            }

            parent::saveLog('Lecturer viewed students in their groups.');

            return response()->json([
                'message' => 'Students retrieved successfully.',
                'data' => ['students' => $students],
            ], 200);
        } catch (\Exception $e) {
            \Log::info('Sorry there:'. $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch students.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

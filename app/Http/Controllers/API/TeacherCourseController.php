<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Group;
use App\Models\GroupMember;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TeacherCourseController extends Controller
{
    /**
     * Display a listing of courses.
     */

    /**
     * @OA\Get(
     *     path="/api/lecturer/courses",
     *     tags={"Lecturer - Courses"},
     *     summary="List all courses",
     *     description="Returns a list of all available courses. If no courses are found, a 404 status is returned.",
     *     @OA\Response(
     *         response=200,
     *         description="List of courses",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 properties={
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Bachelor in Computer Application"),
     *                     @OA\Property(property="description", type="string", nullable=true, example=null),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2024-01-02"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-01-02"),
     *                     @OA\Property(property="schedule", type="string", nullable=true, example=null),
     *                     @OA\Property(property="created_by", type="integer", example=2),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-05T16:22:56.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-05T16:22:56.000000Z")
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No courses found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No data found")
     *         )
     *     )
     * )
     */

    public function index()
    {
        try {
            $lecturerId = Auth::id();

            // Get group IDs for the lecturer
            $groupIds = GroupMember::where('user_id', $lecturerId)->pluck('group_id');

            if ($groupIds->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not assigned to any groups yet.'
                ], 404);
            }

            // Get course IDs from groups
            $courseIds = Group::whereIn('id', $groupIds)->pluck('course_id');

            if ($courseIds->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No courses found for your groups.'
                ], 404);
            }

            // Get course data
            $courses = Course::whereIn('id', $courseIds)->get();

            if ($courses->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Looks like you don\'t have any courses assigned yet.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Courses fetched successfully.',
                'data' => $courses
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching lecturer courses: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching courses.'
            ], 500);
        }
    }

   
}

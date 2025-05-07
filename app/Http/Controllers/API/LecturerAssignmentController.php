<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseLecturerAssignment;
use App\Models\Group;
use App\Models\GroupMember;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LecturerAssignmentController extends Controller
{
    /**
     * Display a listing of assignments for the authenticated lecturer.
     */
    /**
     * @OA\Get(
     *     path="/api/lecturer/assignments",
     *     summary="Get assignments for authenticated lecturer",
     *     description="Returns a list of assignments created by the authenticated lecturer along with course information.",
     *     operationId="getLecturerAssignments",
     *     tags={"Assignments"},
     *     security={{"sanctum": {}}},
     * 
     *     @OA\Response(
     *         response=200,
     *         description="List of assignments",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="course_id", type="integer", example=2),
     *                     @OA\Property(property="lecturer_id", type="integer", example=5),
     *                     @OA\Property(property="title", type="string", example="This is title of the assignment"),
     *                     @OA\Property(property="description", type="string", example="This is description of the assignment"),
     *                     @OA\Property(property="due_date", type="string", format="date", example="2025-04-22"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-21T18:35:06.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-21T18:35:06.000000Z"),
     *                     @OA\Property(property="links", type="string", example="https:www.k.com"),
     *                     @OA\Property(
     *                         property="course",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Bachelor in Computer Application")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Something went wrong while fetching assignments.")
     *         )
     *     )
     * )
     */

    public function index(Request $request)
    {
        try {
            $lecturerId = $request->user()->id;

            $assignments = Assignment::with('course:id,name')
                ->where('lecturer_id', $lecturerId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assignments
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching assignments: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching assignments.'
            ], 500);
        }
    }

    /**
     * Get the course that is assigned to the lecturer
     */
    /**
     * @OA\Get(
     *     path="/api/lecturer/create-assignments",
     *     summary="Fetch courses assigned to the lecturer",
     *     description="Returns a list of courses associated with the authenticated lecturer's groups. This is used when creating new assignments.",
     *     operationId="getLecturerCoursesForAssignmentCreation",
     *     tags={"Assignments"},
     *     security={{"sanctum": {}}},
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Courses fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Courses fetched successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Bachelor in Computer Application")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lecturer not assigned to groups or no courses found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You are not assigned to any groups yet.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error while fetching courses",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Something went wrong while fetching courses.")
     *         )
     *     )
     * )
     */

    public function create()
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
            $courses = Course::whereIn('id', $courseIds)->get(['id', 'name']);

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
    /**
     * Store a newly created assignment.
     * Validates that the lecturer is assigned to the course.
     */
    /**
     * @OA\Post(
     *     path="/api/lecturer/assignments",
     *     summary="Create a new assignment",
     *     description="Creates an assignment for a course assigned to the authenticated lecturer.",
     *     operationId="createAssignment",
     *     tags={"Assignments"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id", "title", "description", "due_date", "links"},
     *             @OA\Property(property="course_id", type="integer", example=2),
     *             @OA\Property(property="title", type="string", example="This is title of the assignment 2"),
     *             @OA\Property(property="description", type="string", example="This is description of the assignment"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2025-04-23"),
     *             @OA\Property(property="links", type="string", example="https://www.k.com")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Assignment created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Assignment created successfully."),
     *             @OA\Property(
     *                 property="assignment",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="course_id", type="integer", example=2),
     *                 @OA\Property(property="title", type="string", example="This is title of the assignment 2"),
     *                 @OA\Property(property="description", type="string", example="This is description of the assignment"),
     *                 @OA\Property(property="due_date", type="string", format="date", example="2025-04-23"),
     *                 @OA\Property(property="links", type="string", example="https://www.k.com"),
     *                 @OA\Property(property="lecturer_id", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", example="2025-04-22T02:46:16.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-04-22T02:46:16.000000Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"title": {"The title field is required."}}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while creating the assignment.")
     *         )
     *     )
     * )
     */

    public function store(Request $request)
    {
        try {
            $lecturerId = $request->user()->id;

            // Validate request
            $validatedData = $request->validate([
                'course_id'   => 'required|exists:courses,id',
                'title'       => 'required|string|max:255',
                'description' => 'required|string',
                'due_date'    => 'required|date|after:today',
                'links' => 'required|string',
            ]);

            // Check if the lecturer is assigned to the course
            // $courseAssignment = CourseLecturerAssignment::where('course_id', $validatedData['course_id'])
            //     ->where('lecturer_id', $lecturerId)
            //     ->first();

            // if (!$courseAssignment) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'You are not assigned to this course.'
            //     ], 403);
            // }

            $validatedData['lecturer_id'] = $lecturerId;

            $assignment = Assignment::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Assignment created successfully.',
                'assignment' => $assignment
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Requested model not found.'
            ], 404);
        } catch (Exception $e) {
            Log::error('Assignment creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while creating the assignment.'
            ], 500);
        }
    }

    /**
     * Display the specified assignment.
     */
    // public function show(Request $request, $id)
    // {
    //     $lecturerId = $request->user()->id;
    //     $assignment = Assignment::with('course')->findOrFail($id);

    //     // Ensure the assignment belongs to the authenticated lecturer.
    //     if ($assignment->lecturer_id != $lecturerId) {
    //         return response()->json(['error' => 'Unauthorized'], 403);
    //     }

    //     return response()->json($assignment);
    // }

    /**
     * Update the specified assignment.
     */

    /**
     * @OA\Put(
     *     path="/api/lecturer/assignments/{id}",
     *     summary="Update an existing assignment",
     *     description="Allows a lecturer to update their own assignment.",
     *     operationId="updateAssignment",
     *     tags={"Assignments"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the assignment to update",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id", "title", "description", "due_date", "links"},
     *             @OA\Property(property="course_id", type="integer", example=2),
     *             @OA\Property(property="title", type="string", example="This is title of the assignments update 1"),
     *             @OA\Property(property="description", type="string", example="This is description of the assignment"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2025-04-23"),
     *             @OA\Property(property="links", type="string", example="https://www.k.com")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Assignment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Assignment updated successfully."),
     *             @OA\Property(
     *                 property="assignment",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="course_id", type="integer", example=2),
     *                 @OA\Property(property="lecturer_id", type="integer", example=5),
     *                 @OA\Property(property="title", type="string", example="This is title of the assignments update 1"),
     *                 @OA\Property(property="description", type="string", example="This is description of the assignment"),
     *                 @OA\Property(property="due_date", type="string", format="date", example="2025-04-23"),
     *                 @OA\Property(property="created_at", type="string", example="2025-04-21T18:35:06.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-04-22T02:47:44.000000Z"),
     *                 @OA\Property(property="links", type="string", example="https://www.k.com")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized access to update this assignment.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Assignment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Assignment not found.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"title": {"The title field is required."}}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while updating the assignment.")
     *         )
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        try {
            $lecturerId = $request->user()->id;

            // Attempt to find the assignment
            $assignment = Assignment::findOrFail($id);

            // Check if the authenticated lecturer owns this assignment
            if ($assignment->lecturer_id != $lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to update this assignment.'
                ], 403);
            }

            // Validate the input data
            $validatedData = $request->validate([
                'course_id'   => 'required|exists:courses,id',
                'title'       => 'required|string|max:255',
                'description' => 'required|string',
                'due_date'    => 'required|date|after:today',
                'links'       => 'required|string',
            ]);

            // Update the assignment
            $assignment->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully.',
                'assignment' => $assignment
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found.'
            ], 404);
        } catch (Exception $e) {
            Log::error('Error updating assignment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while updating the assignment.'
            ], 500);
        }
    }
    /**
     * Remove the specified assignment.
     */
    /**
     * @OA\Delete(
     *     path="/api/lecturer/assignments/{id}",
     *     summary="Delete an assignment",
     *     description="Allows a lecturer to delete their own assignment by ID.",
     *     operationId="deleteAssignment",
     *     tags={"Assignments"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the assignment to delete",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Assignment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Assignment deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Assignment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Assignment] 1")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $lecturerId = $request->user()->id;
        $assignment = Assignment::findOrFail($id);

        if ($assignment->lecturer_id != $lecturerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $assignment->delete();

        return response()->json(['message' => 'Assignment deleted successfully']);
    }
}

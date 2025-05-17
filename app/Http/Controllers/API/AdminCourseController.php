<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class AdminCourseController extends Controller
{
    /**
     * Display a listing of courses.
     */

    /**
     * @OA\Get(
     *     path="/api/admin/courses",
     *     tags={"Admin - Courses"},
     *     summary="List all courses",
     *     description="Returns a list of all available courses. If no courses are found, a 204 status is returned.",
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
        $courses = Course::all();
        if ($courses->isEmpty()) {
            return response()->json([
                'message' => 'No data found'
            ], 404);
        }
        return response()->json($courses);
    }

    /**
     * Store a newly created course in storage.
     */

    /**
     * @OA\Post(
     *     path="/api/admin/courses",
     *     tags={"Admin - Courses"},
     *     summary="Create a new course",
     *     description="Creates a new course with provided details. The course is assigned to the currently authenticated admin.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "start_date", "end_date"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Bachelor in Computer Application"),
     *             @OA\Property(property="description", type="string", nullable=true, example="A program focused on computer science."),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-01-02"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-01-02"),
     *             @OA\Property(property="schedule", type="string", nullable=true, example="Mon-Fri, 9AM to 12PM")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Course created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course created successfully"),
     *             @OA\Property(property="course", type="object",
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="name", type="string", example="Bachelor in Computer Application"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2024-01-02"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-01-02"),
     *                 @OA\Property(property="created_by", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-06T16:01:33.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-06T16:01:33.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation Error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Something Error occurred",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something Error occurred")
     *         )
     *     )
     * )
     */

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'schedule' => 'nullable|string',
                'faculty_name' => 'nullable|string',
                'teacher_name' => 'nullable|string',
                'batch' => 'nullable|string',
            ]);

            // Automatically assign the course to the authenticated admin
            $validatedData['created_by'] = auth()->user()->id;

            $course = Course::create($validatedData);

            return response()->json([
                'message' => 'Course created successfully',
                'course' => $course
            ], 201);
        } catch (ValidationException $ex) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $ex->errors(),
            ], 422);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Something Error occurred',
            ], 500);
        }
    }

    /**
     * Display the specified course.
     */

    /**
     * @OA\Get(
     *     path="/api/admin/courses/{id}",
     *     tags={"Admin - Courses"},
     *     summary="Get details of a specific course",
     *     description="Fetches and returns a course by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the course",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with course data",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=2),
     *             @OA\Property(property="name", type="string", example="Bachelor in Computer Application"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-01-02"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-01-02"),
     *             @OA\Property(property="schedule", type="string", nullable=true),
     *             @OA\Property(property="created_by", type="integer", example=2),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-05T16:22:56.000000Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-05T16:22:56.000000Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please check the Id of the course then try again")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something Error occurred")
     *         )
     *     )
     * )
     */

    public function show($id)
    {
        try {
            $course = Course::findOrFail($id);
            return response()->json($course);
        } catch (ModelNotFoundException $mnfe) {
            return response()->json([
                'message' => 'Please check the Id of the course then try again',
            ], 404);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Something Error occurred',
            ], 500);
        }
    }

    /**
     * Update the specified course in storage.
     */

    /**
     * @OA\Put(
     *     path="/api/admin/courses/{id}",
     *     tags={"Admin - Courses"},
     *     summary="Update a course",
     *     description="Update the details of a specific course by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the course to be updated",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Bachelor in Computer Application"),
     *             @OA\Property(property="description", type="string", example="This is a bachelor in Application description"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-01-02"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-01-02"),
     *             @OA\Property(property="schedule", type="string", example="Monday to Friday, 10 AM - 2 PM")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course updated successfully"),
     *             @OA\Property(
     *                 property="course",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Bachelor in Computer Application"),
     *                 @OA\Property(property="description", type="string", example="This is a bachelor in Application description"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2024-01-02"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-01-02"),
     *                 @OA\Property(property="schedule", type="string", example="Monday to Friday, 10 AM - 2 PM"),
     *                 @OA\Property(property="created_by", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-05T16:22:56.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-06T16:04:52.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please check the Id of the user then try again")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation Error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something Error occurred")
     *         )
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        try {
            $course = Course::findOrFail($id);
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date|after_or_equal:start_date',
                'schedule' => 'nullable|string',
                'faculty_name' => 'nullable|string',
                'teacher_name' => 'nullable|string',
                'batch' => 'nullable|string',
            ]);

            $course->update($validatedData);

            return response()->json([
                'message' => 'Course updated successfully',
                'course' => $course
            ]);
        } catch (ModelNotFoundException $mnfe) {
            return response()->json([
                'message' => 'Please check the Id of the user then try again',
            ], 404);
        } catch (ValidationException $ex) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $ex->errors(),
            ], 422);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Something Error occurred',
            ], 500);
        }
    }

    /**
     * Remove the specified course from storage.
     */

    /**
     * @OA\Delete(
     *     path="/api/admin/courses/{id}",
     *     tags={"Admin - Courses"},
     *     summary="Delete a course",
     *     description="Deletes a specific course by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the course to delete",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please check the Id of the user then try again")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something Error occurred")
     *         )
     *     )
     * )
     */

    public function destroy($id)
    {
        try {
            $course = Course::findOrFail($id);
            $course->delete();
            return response()->json(['message' => 'Course deleted successfully']);
        } catch (ModelNotFoundException $mnfe) {
            return response()->json([
                'message' => 'Please check the Id of the user then try again',
            ], 404);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Something Error occurred',
            ], 500);
        }
    }
}

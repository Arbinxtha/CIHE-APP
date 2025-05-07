<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\DM_Basecontroller;
use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\SecurityLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EnrollmentController extends DM_Basecontroller
{
    /**
     * Display a listing of enrollments.
     * - Admin users see all enrollments.
     * - Student users see only their own enrollments.
     */

    /**
     * @OA\Get(
     *     path="/api/enrollments",
     *     tags={"Enrollments"},
     *     summary="Get enrollments",
     *     description="Returns a list of enrollments. Admins get all enrollments, while students get their own.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of enrollments",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="course_id", type="integer", example=2),
     *                 @OA\Property(property="student_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-05T17:02:25.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-05T17:02:25.000000Z"),
     *                 @OA\Property(
     *                     property="course",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Bachelor in Computer Application")
     *                 ),
     *                 @OA\Property(
     *                     property="student",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="first_name", type="string", example="Sanjaya")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */

    public function index(Request $request)
    {
        $user = $request->user();

        parent::saveLog('has seen the list of the enrollments');
        if ($user->role === 'admin') {
            $enrollments = Enrollment::with('course:id,name', 'student:id,first_name')->get();
        } else if ($user->role === 'student') {
            $enrollments = Enrollment::with('course:id,name')
                ->where('student_id', $user->id)
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($enrollments->isEmpty()) {
            return response()->json([
                'message' => 'No data found'
            ], 404);
        }
        return response()->json($enrollments);
    }

    /**
     * Get list of course that was added by the admin
     *  for the students for the enrollments purposes
     */

    /**
     * @OA\Get(
     *     path="/api/enrollments/courses-list",
     *     tags={"Enrollments"},
     *     summary="Get list of available courses for enrollment",
     *     description="Returns all courses that were added by the admin. This list is used for student enrollments.",
     *     @OA\Response(
     *         response=200,
     *         description="List of courses or a message if none found",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Bachelor in Computer Application"),
     *                         @OA\Property(property="description", type="string", example="This is a bachelor in Application description"),
     *                         @OA\Property(property="start_date", type="string", format="date", example="2024-01-02"),
     *                         @OA\Property(property="end_date", type="string", format="date", example="2025-01-02"),
     *                         @OA\Property(property="schedule", type="string", nullable=true, example=null),
     *                         @OA\Property(property="created_by", type="integer", example=2),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-05T16:22:56.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-06T16:04:52.000000Z")
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", example="No data found")
     *                 )
     *             }
     *         )
     *     )
     * )
     */

    public function getCourse()
    {
        parent::saveLog('has requested to see the course list');
        $courses = Course::get();
        if ($courses->isEmpty()) {
            return response()->json([
                'message' => 'No data found'
            ], 200);
        }
        return response()->json($courses);
    }

    /**
     * Store a newly created enrollment.
     * Only a student should be able to enroll in a course.
     */

    /**
     * @OA\Post(
     *     path="/api/enrollments",
     *     tags={"Enrollments"},
     *     summary="Enroll a student in a course",
     *     description="Allows a student user to enroll in a course. Prevents duplicate enrollments and ensures only students can access this endpoint.",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id"},
     *             @OA\Property(property="course_id", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Enrollment created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Enrollment created successfully"),
     *             @OA\Property(property="enrollment", type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="course_id", type="integer", example=3),
     *                 @OA\Property(property="student_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-06T16:13:37.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-06T16:13:37.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Only students can enroll in courses",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Only students can enroll in courses")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Student already enrolled in the course",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="You are already enrolled in this course")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="course_id", type="array", @OA\Items(type="string", example="The selected course_id is invalid."))
     *             )
     *         )
     *     )
     * )
     */

    public function store(Request $request)
    {
        $user = $request->user();

        // Only students can create enrollments
        if ($user->role !== 'student') {
            return response()->json(['error' => 'Only students can enroll in courses'], 403);
        }

        $validatedData = $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        // Check if the student is already enrolled in the course.
        if (Enrollment::where('course_id', $validatedData['course_id'])
            ->where('student_id', $user->id)
            ->exists()
        ) {
            return response()->json(['error' => 'You are already enrolled in this course'], 409);
        }

        // Create enrollment with default status 'pending'
        $enrollment = Enrollment::create([
            'course_id'  => $validatedData['course_id'],
            'student_id' => $user->id,
            'status'     => 'pending',
        ]);
        parent::saveLog('Enrolled in the "' . parent::getCourseNameByID($validatedData['course_id']) . '" course.');
        $student = Auth::user();
        $student->notify(new \App\Notifications\CustomNotification(
            'Enrollment Successful',
            'You have successfully enrolled in the course.',
        ));

        return response()->json([
            'message'    => 'Enrollment created successfully',
            'enrollment' => $enrollment,
        ], 201);
    }

    /**
     * Display the specified enrollment.
     * Admins can view any enrollment; students only view their own.
     */

    /**
     * @OA\Get(
     *     path="/api/enrollments/{id}",
     *     tags={"Enrollments"},
     *     summary="View a specific enrollment",
     *     description="Admins can view any enrollment. Students can view only their own enrollment.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the enrollment",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Enrollment details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=2),
     *             @OA\Property(property="student_id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-05T17:02:25.000000Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-05T17:02:25.000000Z"),
     *             @OA\Property(property="course", type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Bachelor in Computer Application")
     *             ),
     *             @OA\Property(property="student", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="Sanjaya")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Enrollment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Enrollment] 999")
     *         )
     *     )
     * )
     */

    public function show(Request $request, $id)
    {
        try {
            $enrollment = Enrollment::with('course:id,name', 'student:id,first_name')->findOrFail($id);
            parent::saveLog('Viewed the enrollments.');

            $user = $request->user();

            // If user is student, ensure they own the enrollment.
            if ($user->role === 'student' && $enrollment->student_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json($enrollment);
        } catch (ModelNotFoundException $mnfe) {
            return response()->json([
                'message' => 'Please check the Id of the user then try again',
            ], 401);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Something Error occurred',
            ], 500);
        }
    }

    /**
     * Update the specified enrollment.
     * - Admins can update the enrollment status (approve/reject).
     * - Students can cancel (or delete) their own enrollment if allowed.
     */

    /**
     * @OA\Put(
     *     path="/api/enrollments/{id}",
     *     tags={"Enrollments"},
     *     summary="Update a specific enrollment",
     *     description="Admins can update the enrollment status (approve/reject), while students can cancel (or delete) their own enrollment if allowed.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the enrollment",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="approved", description="Only for admins to update the enrollment status"),
     *                 @OA\Property(property="course_id", type="integer", example=2, description="Only for students to update their own enrollment")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Enrollment status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Enrollment status updated successfully"),
     *             @OA\Property(property="enrollment", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="course_id", type="integer", example=2),
     *                 @OA\Property(property="student_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="approved"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-05T17:02:25.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-06T16:19:28.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Enrollment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Enrollment not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation Error"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="status", type="array", items=@OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */

    public function update(Request $request, $id)
    {

        $enrollment = Enrollment::findOrFail($id);
        $user = $request->user();
        parent::saveLog('Updated the enrollment details.');

        // For admins: allow updating the enrollment status.
        if ($user->role === 'admin') {
            $validatedData = $request->validate([
                'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            ]);

            $enrollment->update($validatedData);
            return response()->json([
                'message'    => 'Enrollment status updated successfully',
                'enrollment' => $enrollment,
            ]);
        }
        if ($user->role === 'student') {
            $validatedData = $request->validate([
                'course_id' => 'required|exists:courses,id',
            ]);
            $enrollment->update($validatedData);
            return response()->json([
                'message'    => 'Enrollment status updated successfully',
                'enrollment' => $enrollment,
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    /**
     * Remove the specified enrollment.
     * Admins can delete any enrollment.
     * Students can delete (cancel) their own enrollment.
     */
    public function destroy(Request $request, $id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $user = $request->user();

        // If admin or student (and own enrollment), allow deletion.
        if ($user->role === 'admin' || ($user->role === 'student' && $enrollment->student_id === $user->id)) {
            $enrollment->delete();
            parent::saveLog('Deleted an enrollment.');
            return response()->json(['message' => 'Enrollment deleted successfully']);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }
}

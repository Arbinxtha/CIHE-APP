<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\CustomNotification;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class StudentSubmissionController extends Controller
{
    /**
     * List all submissions for the authenticated student.
     */
    /**
 * @OA\Get(
 *     path="/api/student/submissions",
 *     summary="Get assignments for the logged-in student",
 *     description="Retrieves assignments based on the student's enrolled groups and courses.",
 *     operationId="getStudentAssignments",
 *     tags={"Student Assignments"},
 *     security={{"sanctum": {}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="Assignments retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Assignments retrieved successfully."),
 *             @OA\Property(
 *                 property="assignments",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=2),
 *                     @OA\Property(property="course_id", type="integer", example=2),
 *                     @OA\Property(property="lecturer_id", type="integer", example=5),
 *                     @OA\Property(property="title", type="string", example="This is title of the assignment"),
 *                     @OA\Property(property="description", type="string", example="This is  description of the assignment"),
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
 *
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized access. Only students can view assignments.",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Unauthorized access. Only students can view assignments.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="No groups, courses, or lecturers found.",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="You are not assigned to any groups yet.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Server error while retrieving assignments.",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="An unexpected error occurred while retrieving assignments.")
 *         )
 *     )
 * )
 */

    public function index(Request $request)
    {
        try {
            $student = $request->user();
    
            if ($student->role !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access. Only students can view assignments.'
                ], 401);
            }
    
            // Get group IDs for the student
            $groupIds = GroupMember::where('user_id', $student->id)->pluck('group_id');
    
            if ($groupIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to any groups yet.'
                ], 404);
            }
    
            // Get course IDs for those groups
            $courseIds = Group::whereIn('id', $groupIds)->pluck('course_id');
    
            if ($courseIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No courses found for your groups.'
                ], 404);
            }
    
            // Get lecturer IDs associated with those groups
            $lecturerIds = GroupMember::whereIn('group_id', $groupIds)
                ->join('users', 'group_members.user_id', '=', 'users.id')
                ->where('users.role', 'lecturer')
                ->pluck('users.id');
    
            if ($lecturerIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No lecturers found for your groups.'
                ], 404);
            }
    
            // Fetch assignments
            $assignments = Assignment::with('course:id,name') // eager load course
                ->whereIn('course_id', $courseIds)
                ->whereIn('lecturer_id', $lecturerIds)
                ->get();
    
            if ($assignments->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No assignments found yet.',
                    'assignments' => []
                ], 200);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Assignments retrieved successfully.',
                'assignments' => $assignments
            ], 200);
    
        } catch (Exception $e) {
            Log::error('Error fetching student assignments: ' . $e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while retrieving assignments.'
            ], 500);
        }
    }

    /**
     * Create a new submission for an assignment.
     */
    // public function store(Request $request)
    // {
    //     $student = $request->user();

    //     $validatedData = $request->validate([
    //         'assignment_id' => 'required|exists:assignments,id',
    //         'content'       => 'nullable|string',
    //         'file_url'      => 'file|max:2048',
    //     ]);

    //     // Optionally, ensure the student hasn't already submitted for this assignment.
    //     if (Submission::where('assignment_id', $validatedData['assignment_id'])
    //         ->where('student_id', $student->id)
    //         ->exists()
    //     ) {
    //         return response()->json(['error' => 'You have already submitted this assignment'], 409);
    //     }


    //     if ($request->hasFile('file_url')) {
    //         $file = $request->file('file_url');
    //         // Generate a unique file name, e.g., using the current timestamp and original file name
    //         $filename = time() . '_' . $file->getClientOriginalName();

    //         // Define a destination path in your public folder (e.g., public/uploads/submissions)
    //         $destinationPath = 'uploads/submissions';

    //         // Move the file to the destination path
    //         $file->move($destinationPath, $filename);

    //         // Build the relative path to be stored in the database
    //         $path = 'uploads/submissions/' . $filename;

    //         // Now, you can save $path to your DB along with other submission data
    //         $validatedData['file_url'] = $path;
    //     }


    //     $validatedData['student_id'] = $student->id;
    //     $validatedData['status'] = 'submitted'; // default status

    //     $submission = Submission::create($validatedData);
    //     // Retrieve the assignment to get the associated teacher (lecturer)
    //     $assignment = Assignment::findOrFail($validatedData['assignment_id']);

    //     $teacher = $assignment->lecturer;

    //     // Send notification to the teacher
    //     $teacher->notify(new CustomNotification(
    //         'Assignment Submitted',
    //         "A student has submitted the assignment: {$assignment->title}. Please review it.",
    //         url('lecturer/submissions/', $assignment->id) // adjust this route as needed
    //     ));
    //     return response()->json([
    //         'message'    => 'Submisasion created successfully',
    //         'submission' => $submission
    //     ], 201);
    // }

    /**
     * Show a specific submission.
     */
    // public function show(Request $request, $id)
    // {
    //     $student = $request->user();
    //     $submission = Submission::with('assignment')->findOrFail($id);
    //     if ($submission->student_id != $student->id) {
    //         return response()->json(['error' => 'Unauthorized'], 403);
    //     }
    //     return response()->json($submission);
    // }

    /**
     * Update an existing submission.
     * (For example, a student may update their submission if it hasn't been graded yet.)
     */
    public function update(Request $request, $id)
    {
        $student = $request->user();
        $submission = Submission::findOrFail($id);
        if ($submission->student_id != $student->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        // Optionally prevent updates once grading has occurred.
        if ($submission->status != 'submitted') {
            return response()->json(['error' => 'Cannot update submission after it has been graded'], 403);
        }

        if ($request->hasFile('file_url')) {

            if (File::exists($submission->file_url)) {
                File::delete($submission->file_url);
            }
            $file = $request->file('file_url');
            // Generate a unique file name, e.g., using the current timestamp and original file name
            $filename = time() . '_' . $file->getClientOriginalName();

            // Define a destination path in your public folder (e.g., public/uploads/submissions)
            $destinationPath = 'uploads/submissions';

            // Move the file to the destination path
            $file->move($destinationPath, $filename);

            // Build the relative path to be stored in the database
            $path = 'uploads/submissions/' . $filename;

            // Now, you can save $path to your DB along with other submission data
            $validatedData['file_url'] = $path;
        }
        $validatedData = $request->validate([
            'content'  => 'nullable|string',
            'file_url' => 'file|string',
        ]);
        $submission->update($validatedData);
        return response()->json([
            'message'    => 'Submission updated successfully',
            'submission' => $submission
        ]);
    }

    /**
     * Delete a submission (if allowed).
     */
    public function destroy(Request $request, $id)
    {
        $student = $request->user();
        $submission = Submission::findOrFail($id);
        if ($submission->student_id != $student->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        // Optionally restrict deletion if submission is no longer in "submitted" status.
        if ($submission->status != 'submitted') {
            return response()->json(['error' => 'Cannot delete submission after it has been graded'], 403);
        }
        $submission->delete();
        return response()->json(['message' => 'Submission deleted successfully']);
    }
}

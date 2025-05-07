<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Notifications\CustomNotification;

class LecturerSubmissionController extends Controller
{
    /**
     * List submissions for assignments created by the authenticated lecturer.
     */
    public function index(Request $request)
    {
        $lecturer = $request->user();
        $submissions = Submission::with('assignment', 'student')
            ->whereHas('assignment', function ($query) use ($lecturer) {
                $query->where('lecturer_id', $lecturer->id);
            })
            ->get();
        return response()->json($submissions);
    }

    /**
     * Show details of a specific submission.
     */
    public function show(Request $request, $id)
    {
        $lecturer = $request->user();
        $submission = Submission::with('assignment', 'student')->findOrFail($id);
        if ($submission->assignment->lecturer_id != $lecturer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json($submission);
    }

    /**
     * Update submission status and add feedback.
     * For example, lecturers can approve or reject a submission.
     */
    public function update(Request $request, $id)
    {
        $lecturer = $request->user();
        $submission = Submission::with('assignment')->findOrFail($id);
        if ($submission->assignment->lecturer_id != $lecturer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $validatedData = $request->validate([
            'status'   => 'required|in:approved,rejected',
            'feedback' => 'nullable|string',
        ]);
        $submission->update($validatedData);
        $assignment = Assignment::findOrFail($validatedData['assignment_id']);

        $student = $assignment->student;

        // Send notification to the teacher
        $student->notify(new CustomNotification(
            'Assignment Reviewed',
            "Lectures has reviewed the assignment: {$assignment->title}. Please check  it.",
            url('student/submissions/', $assignment->id) // adjust this route as needed
        ));
        return response()->json([
            'message'    => 'Submission updated successfully',
            'submission' => $submission
        ]);
    }
}

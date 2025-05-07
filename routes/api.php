<?php

use App\Http\Controllers\API\AdminCourseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AdminUserController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\EnrollmentController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\GroupMemberController;
use App\Http\Controllers\API\LecturerAssignmentController;
use App\Http\Controllers\API\LecturerSubmissionController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\StudentSubmissionController;
use App\Http\Controllers\API\TeacherCourseController;
use Illuminate\Support\Facades\Broadcast;

Route::post('/login', [AuthController::class, 'login']);

// Logout endpoint protected by Sanctum authentication
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'admin']], function () {
  // List all lecturer and student accounts
  Route::get('users', [AdminUserController::class, 'index']);

  // Create a new lecturer or student account
  Route::post('users', [AdminUserController::class, 'store']);

  // Get details of a specific user
  Route::get('users/{id}', [AdminUserController::class, 'show']);

  // Update an existing user account
  Route::put('users/{id}', [AdminUserController::class, 'update']);

  // Delete a user account
  Route::delete('users/{id}', [AdminUserController::class, 'destroy']);

  /**
   * Course api
   */

  // List all courses
  Route::get('courses', [AdminCourseController::class, 'index']);

  // Create a new course
  Route::post('courses', [AdminCourseController::class, 'store']);

  // Get details of a specific course
  Route::get('courses/{id}', [AdminCourseController::class, 'show']);

  // Update an existing course
  Route::put('courses/{id}', [AdminCourseController::class, 'update']);

  // Delete a course
  Route::delete('courses/{id}', [AdminCourseController::class, 'destroy']);

  // Group management routes

  Route::get('groups', [GroupController::class, 'index']);
  Route::post('groups', [GroupController::class, 'store']);
  Route::get('groups/{id}', [GroupController::class, 'show']);
  Route::put('groups/{id}', [GroupController::class, 'update']);
  Route::delete('groups/{id}', [GroupController::class, 'destroy']);

  // Group member management routes
  Route::get('groups/{group_id}/members', [GroupMemberController::class, 'index']);
  Route::post('groups/{group_id}/members', [GroupMemberController::class, 'store']);
  // Route::get('groups/{group_id}/members/{user_id}', [GroupMemberController::class, 'show']);
  // Route::put('groups/{group_id}/members/{user_id}', [GroupMemberController::class, 'update']);
  Route::delete('groups/{group_id}/members/{id}', [GroupMemberController::class, 'destroy']);



  // Batch add members to a group
  Route::post('groups/{group_id}/members/batch/store', [GroupMemberController::class, 'storeMultiple']);

  // Batch delete members from a group
  Route::delete('groups/{group_id}/members/batch/delete', [GroupMemberController::class, 'deleteMultiple']);
});



Route::middleware('auth:sanctum')->group(function () {
  // List enrollments – if admin, returns all enrollments; if student, returns only own enrollments.
  Route::get('enrollments', [EnrollmentController::class, 'index']);

  //Course list for the students for the enrollments
  Route::get('enrollments/courses-list', [EnrollmentController::class, 'getCourse']);

  // Create a new enrollment – typically by a student.
  Route::post('enrollments', [EnrollmentController::class, 'store']);


  // Show a specific enrollment (only if authorized).
  Route::get('enrollments/{id}', [EnrollmentController::class, 'show']);

  // Update an enrollment – for example, admin can update status (approve/reject), or a student may cancel.
  Route::put('enrollments/{id}', [EnrollmentController::class, 'update']);

  // Delete an enrollment (e.g., a student cancels an enrollment or an admin removes one).
  Route::delete('enrollments/{id}', [EnrollmentController::class, 'destroy']);

  /**
   * Get the list of users according to the permissions
   */

   Route::get('/get-people',[MessageController::class,'indexpage']);
   Route::get('/get-messages/{receiver_id}',[MessageController::class,'getMessages']);
   Route::post('/send-messages',[MessageController::class,'sendMessages']);

   Broadcast::routes(['middleware' => ['auth:sanctum']]);


});

Route::group(['prefix' => 'lecturer', 'middleware' => ['auth:sanctum', 'lecturer']], function () {
  // List all assignments for the authenticated lecturer
  Route::get('assignments', [LecturerAssignmentController::class, 'index']);
  Route::get('create-assignments', [LecturerAssignmentController::class, 'create']);
  // Create a new assignment for a course the lecturer is assigned to
  Route::post('assignments', [LecturerAssignmentController::class, 'store']);

  // Get details of a specific assignment
  Route::get('assignments/{id}', [LecturerAssignmentController::class, 'show']);

  // Update an assignment
  Route::put('assignments/{id}', [LecturerAssignmentController::class, 'update']);

  // Delete an assignment
  Route::delete('assignments/{id}', [LecturerAssignmentController::class, 'destroy']);

  // List submissions for assignments created by the lecturer
  Route::get('submissions', [LecturerSubmissionController::class, 'index']);

  // View details of a specific submission
  Route::get('submissions/{id}', [LecturerSubmissionController::class, 'show']);

  // Update submission status and add feedback (approve/reject)
  Route::put('submissions/{id}', [LecturerSubmissionController::class, 'update']);

  Route::get('courses', [TeacherCourseController::class, 'index']);

});


Route::group(['prefix' => 'student', 'middleware' => ['auth:sanctum']], function () {
  // List submissions for the authenticated student
  Route::get('submissions', [StudentSubmissionController::class, 'index']);

  // Create a new submission
  Route::post('submissions', [StudentSubmissionController::class, 'store']);

  // View a specific submission
  Route::get('submissions/{id}', [StudentSubmissionController::class, 'show']);

  // Update a submission (if allowed)
  Route::put('submissions/{id}', [StudentSubmissionController::class, 'update']);

  // Delete a submission (if allowed)
  Route::delete('submissions/{id}', [StudentSubmissionController::class, 'destroy']);
});


Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'admin']], function () {
  Route::post('notifications', [\App\Http\Controllers\API\AdminNotificationController::class, 'send']);
});

Route::middleware('auth:sanctum')->group(function () {
  Route::get('view-profile', [ProfileController::class, 'show']);
  Route::put('update-profile', [ProfileController::class, 'update']);
  Route::post('change-password', [ProfileController::class, 'changepassword']);

  // Get all notifications for the current user
  Route::get('notifications', [NotificationController::class, 'index']);

  // Mark a specific notification as read
  Route::put('notifications/{id}/read', [NotificationController::class, 'markAsRead']);

  // Mark all notifications as read
  Route::put('notifications/read-all', [NotificationController::class, 'markAllAsRead']);


  Route::get('dashboard', [DashboardController::class, 'index']);
  Route::get('getstudents', [DashboardController::class, 'getstudents']);

});

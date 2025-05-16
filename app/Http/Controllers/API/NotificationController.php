<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GroupMember;
use App\Models\ScheduledNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NotificationController extends DM_Basecontroller
{
    /**
     * Retrieve all notifications for the authenticated user.
     */
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Get all notifications for the authenticated user",
     *     description="Retrieve the list of notifications for the authenticated user, including their status, data, and timestamps.",
     *     operationId="getNotifications",
     *     tags={"Notifications"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Notifications retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Notifications retrieved successfully."
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="string",
     *                         example="bbcf0cd7-21ef-4323-be46-4490c6cd7692"
     *                     ),
     *                     @OA\Property(
     *                         property="type",
     *                         type="string",
     *                         example="App\\Notifications\\UserMessageNotification"
     *                     ),
     *                     @OA\Property(
     *                         property="notifiable_type",
     *                         type="string",
     *                         example="App\\Models\\User"
     *                     ),
     *                     @OA\Property(
     *                         property="notifiable_id",
     *                         type="integer",
     *                         example=12
     *                     ),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(
     *                             property="message",
     *                             type="string",
     *                             example="Admitted"
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="read_at",
     *                         type="string",
     *                         nullable=true,
     *                         example="null"
     *                     ),
     *                     @OA\Property(
     *                         property="created_at",
     *                         type="string",
     *                         format="date-time",
     *                         example="2025-05-06T01:12:00.000000Z"
     *                     ),
     *                     @OA\Property(
     *                         property="updated_at",
     *                         type="string",
     *                         format="date-time",
     *                         example="2025-05-06T01:12:00.000000Z"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - User not found or not authenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Unauthorized: User not found."
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=false
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Sorry! An internal server error occurred."
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=false
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            // Ensure the user is authenticated (optional if middleware is already set).
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized: User not found.',
                    'status' => false
                ], 401);
            }

            // Fetch notifications and handle potential errors
            $notifications = $user->notifications()->latest()->get();

            // If no notifications exist, return an appropriate message
            if ($notifications->isEmpty()) {
                return response()->json([
                    'message' => 'No notifications found.',
                    'status' => true,
                    'data' => []
                ], 200);
            }

            // Return the notifications if found
            return response()->json([
                'message' => 'Notifications retrieved successfully.',
                'status' => true,
                'data' => $notifications
            ], 200);
        } catch (\Illuminate\Database\QueryException $dbEx) {
            // Handle database-related exceptions
            return response()->json([
                'message' => 'Database error occurred: ' . $dbEx->getMessage(),
                'status' => false
            ], 500);
        } catch (\Exception $ex) {
            // Handle all other exceptions
            Log::error($ex->getMessage()); // Optional: Log the error for debugging
            return response()->json([
                'message' => 'Sorry! An internal server error occurred.',
                'status' => false
            ], 500);
        }
    }


    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All notifications marked as read']);
    }
    /**
     * To store the custom notification
     */

    /**
     * @OA\Post(
     *     path="/api/notifications",
     *     summary="Store a custom notification",
     *     description="Store a custom notification with a message and scheduled time to send.",
     *     operationId="storeNotification",
     *     security={{"bearerAuth":{}}},
     * 
     *     tags={"Notifications"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"user_ids", "message"},
     *                 @OA\Property(
     *                     property="user_ids",
     *                     type="array",
     *                     @OA\Items(type="integer"),
     *                     description="Array of user IDs to send the notification to."
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     description="The message to send in the notification."
     *                 ),
     *                 @OA\Property(
     *                     property="scheduled_at",
     *                     type="string",
     *                     format="date-time",
     *                     description="Scheduled time for sending the notification (optional)."
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Custom notification stored successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Custom Notification stored successfully"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Validation failed."
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 additionalProperties={
     *                     "type": "array",
     *                     "items": {
     *                         "type": "string"
     *                     }
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Sorry ! Internal serve Error occurred"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=false
     *             )
     *         )
     *     )
     * )
     */

    public function store(Request $request)
    {
        try {

            // return response()->json(now());
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*'   => 'exists:users,id',
                'message' => 'required|string',
                'scheduled_at' => [
                    'required',
                    'date',
                ],
            ]);

            ScheduledNotification::create([
                'user_id' => Auth::id(),
                'user_ids' => json_encode($request->user_ids),
                'message' => $request->message,
                'scheduled_at' => $request->scheduled_at,
            ]);

            return response()->json([
                'message' => 'Custom Notification stored successfully',
                'status' => true
            ], 201);
        } catch (ValidationException $vex) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $vex->errors()
            ], 422);
        } catch (\Exception $ex) {
            Log::info($ex->getMessage());
            return response()->json([
                'message' => 'Sorry ! Internal serve Error occurred',
                'status' => false
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/notifications-students",
     *     summary="Get group members based on authenticated user's role and groups",
     *     description="Returns users grouped by group name.",
     *     operationId="getstudents",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="users_grouped_by_group",
     *                 type="object",
     *                 example={
     *                     "Group 1": {
     *                         {
     *                             "id": 5,
     *                             "role": "student",
     *                             "username": "sandip"
     *                         }
     *                     }
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Something went wrong while fetching group members."
     *             )
     *         )
     *     )
     * )
     */
    public function getstudents()
    {
        try {
            $userId = Auth::id();
            $user = Auth::user();
            $role = $user->role;

            // Get group IDs of the current user
            $groupIds = GroupMember::where('user_id', $userId)->pluck('group_id');

            if ($role === 'lecturer') {
                // Students in the same group(s) as the lecturer
                $studentIds = GroupMember::whereIn('group_id', $groupIds)
                    ->where('user_id', '!=', $userId)
                    ->pluck('user_id');

                $students = User::whereIn('id', $studentIds)
                    ->where('role', 'student')
                    ->get(['id', 'role', 'username']);

                parent::saveLog('Lecturer fetched students from same group.');

                return response()->json([
                    'students' => $students,
                ]);
            }else {
                 $studentIds = GroupMember::whereIn('group_id', $groupIds)
                    ->where('user_id', '!=', $userId)
                    ->pluck('user_id');

                $students = User::where('role', 'student')
                    ->get(['id', 'role', 'username']);

                parent::saveLog('Admin fetched students from same group.');

                return response()->json([
                    'students' => $students,
                ]);
            }


            return response()->json([
                'message' => 'Unsupported role.'
            ], 403);
        } catch (\Exception $e) {
            \Log::error('Error fetching user data: ' . $e->getMessage());
            return response()->json([
                'error' => 'Something went wrong while fetching user data.'
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/get-schedule-notification",
     *     summary="Get all scheduled notifications",
     *     description="Returns all scheduled notifications with their ID, message, scheduled time, and sent status.",
     *     operationId="getScheduledNotifications",
     *     tags={"Notifications"},
     * 
     *     @OA\Response(
     *         response=200,
     *         description="List of scheduled notifications",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Schedule Notifications found sucessfully"),
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="message", type="string", example="Training session at 3PM"),
     *                     @OA\Property(property="scheduled_at", type="string", format="date-time", example="2024-05-03 15:00:00"),
     *                     @OA\Property(property="is_sent", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=404,
     *         description="No notifications found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No Schedule Notifications"),
     *             @OA\Property(property="status", type="boolean", example=false)
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Something went wrong while fetching user data.")
     *         )
     *     )
     * )
     */


    public function get_notifications()
    {
        try {
            $data =  ScheduledNotification::get(['id', 'message', 'scheduled_at', 'is_sent']);
            if ($data->isEmpty()) {
                return response()->json([
                    'message' => 'No Schedule Notifications',
                    'status' => false
                ], 404);
            }
            return response()->json([
                'message' => 'Schedule Notifications found sucessfully',
                'status' => true,
                'data' =>  $data
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching notification data: ' . $e->getMessage());
            return response()->json([
                'error' => 'Something went wrong while fetching user data.'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/delete-schedule-notification/{id}",
     *     summary="Delete a scheduled notification",
     *     description="Deletes a scheduled notification by its ID.",
     *     operationId="deleteNotification",
     *     tags={"Notifications"},
     * 
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the scheduled notification to delete",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Notification deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Schedule Notifications deleted sucessfully"),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Sorry no data found according to your request.")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Something went wrong while fetching user data.")
     *         )
     *     )
     * )
     */

    public function deletenotification($id)
    {
        try {
            $data =  ScheduledNotification::findorfail($id);
            $data->Delete();
            return response()->json([
                'message' => 'Schedule Notifications deleted sucessfully',
                'status' => true,
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('Error fetching notification data: ' . $e->getMessage());
            return response()->json([
                'error' => 'Sorry no data found according to your request.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching notification data: ' . $e->getMessage());
            return response()->json([
                'error' => 'Something went wrong while fetching user data.'
            ], 500);
        }
    }

    // /**
    //  * @OA\Get(
    //  *     path="/api/get-schedule-notification-by-month/{month}",
    //  *     summary="Get notification days by month",
    //  *     description="Returns a list of days in the specified month that have scheduled notifications.",
    //  *     operationId="getNotificationsByMonth",
    //  *     tags={"Notifications"},
    //  *
    //  *     @OA\Parameter(
    //  *         name="month",
    //  *         in="path",
    //  *         required=true,
    //  *         description="The month to check (format: YYYY-MM)",
    //  *         @OA\Schema(type="string", example="2024-05")
    //  *     ),
    //  *
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="List of days with notifications",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(
    //  *                 property="days",
    //  *                 type="array",
    //  *                 @OA\Items(type="integer", example=3)
    //  *             )
    //  *         )
    //  *     ),
    //  *
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Server error",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="error", type="string", example="Something went wrong while fetching user data.")
    //  *         )
    //  *     )
    //  * )
    //  */

    // public function notificationbymonth($month)
    // {
    //     try {
    //         // Parse the input month and get the start and end of the month
    //         $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    //         $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

    //         // Fetch all scheduled notifications within the given month
    //         $notifications = ScheduledNotification::whereBetween('scheduled_at', [$startDate, $endDate])->get();

    //         // Extract only the day numbers
    //         $daysWithNotifications = $notifications->map(function ($notification) {
    //             return Carbon::parse($notification->scheduled_at)->day;
    //         })->unique()->values();

    //         return response()->json([
    //             'days' => $daysWithNotifications
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching notification data by month: ' . $e->getMessage());
    //         return response()->json([
    //             'error' => 'Something went wrong while fetching user data.'
    //         ], 500);
    //     }
    // }

    // /**
    //  * @OA\Get(
    //  *     path="/api/get-schedule-notification-by-day/{day}",
    //  *     summary="Get scheduled notifications by day",
    //  *     description="Returns the message, total number of users, user names, scheduled time, and sent status for notifications on a specific day.",
    //  *     operationId="getNotificationsByDay",
    //  *     tags={"Notifications"},
    //  * 
    //  *     @OA\Parameter(
    //  *         name="day",
    //  *         in="path",
    //  *         required=true,
    //  *         description="The date to get notifications for (format: YYYY-MM-DD)",
    //  *         @OA\Schema(type="string", format="date")
    //  *     ),
    //  * 
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Successful response with notification details",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="notifications", type="array",
    //  *                 @OA\Items(
    //  *                     @OA\Property(property="message", type="string", example="Training at 5 PM"),
    //  *                     @OA\Property(property="total_users", type="integer", example=3),
    //  *                     @OA\Property(
    //  *                         property="users",
    //  *                         type="array",
    //  *                         @OA\Items(
    //  *                             @OA\Property(property="id", type="integer", example=1),
    //  *                             @OA\Property(property="name", type="string", example="Alice")
    //  *                         )
    //  *                     ),
    //  *                     @OA\Property(property="scheduled_at", type="string", format="date-time", example="2024-05-03 17:00:00"),
    //  *                     @OA\Property(property="is_sent", type="boolean", example=false)
    //  *                 )
    //  *             )
    //  *         )
    //  *     ),
    //  * 
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Server error",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="error", type="string", example="Something went wrong while fetching data.")
    //  *         )
    //  *     )
    //  * )
    //  */

    // public function notificationbyday($day)
    // {
    //     try {
    //         // Get start and end time for the given day
    //         $startOfDay = Carbon::parse($day)->startOfDay();
    //         $endOfDay = Carbon::parse($day)->endOfDay();

    //         // Fetch all notifications for that day
    //         $notifications = ScheduledNotification::whereBetween('scheduled_at', [$startOfDay, $endOfDay])->get();

    //         // Format response data
    //         $data = $notifications->map(function ($notification) {
    //             $userIds = json_decode($notification->user_ids, true);
    //             $users = User::whereIn('id', $userIds)->get(['id', 'username']);

    //             return [
    //                 'message' => $notification->message,
    //                 'total_users' => $users->count(),
    //                 'users' => $users,
    //                 'scheduled_at' => $notification->scheduled_at,
    //                 'is_sent' => $notification->is_sent,
    //             ];
    //         });

    //         return response()->json([
    //             'notifications' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching notification data by day: ' . $e->getMessage());
    //         return response()->json([
    //             'error' => 'Something went wrong while fetching data.'
    //         ], 500);
    //     }
    // }
/**
 * @OA\Get(
 *     path="/api/get-all-schedule-notification",
 *     summary="Get all unsent scheduled notifications for the current year",
 *     description="Returns a list of all scheduled notifications where is_sent is false and user_id matches the authenticated user.",
 *     operationId="getAllScheduledNotifications",
 *     tags={"Notifications"},
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="List of unsent scheduled notifications",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="events",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="date", type="string", format="date", example="2025-05-13"),
 *                     @OA\Property(property="time", type="string", format="time", example="15:24"),
 *                     @OA\Property(property="Message", type="string", example="Admitted"),
 *                     @OA\Property(
 *                         property="users",
 *                         type="array",
 *                         @OA\Items(
 *                             type="object",
 *                             @OA\Property(property="id", type="integer", example=11),
 *                             @OA\Property(property="username", type="string", example="Arbind Shrestha")
 *                         )
 *                     ),
 *                     @OA\Property(property="is_sent", type="boolean", example=false)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Something went wrong while fetching notification data.")
 *         )
 *     )
 * )
 */

    public function getAllScheduledNotifications()
    {
        try {
            // Get the start and end of the current year
            $startOfYear = Carbon::now()->startOfYear();
            $endOfYear = Carbon::now()->endOfYear();

            // Fetch all unsent notifications for the current year
            $notifications = ScheduledNotification::whereBetween('scheduled_at', [$startOfYear, $endOfYear])
                ->where('is_sent', false)
                ->where('user_id', Auth::id())
                ->get();

            $events = [];

            foreach ($notifications as $notification) {
                $userIds = json_decode($notification->user_ids, true);
                $users = User::whereIn('id', $userIds)->get(['id', 'username']);

                $events[] = [
                    'date' => Carbon::parse($notification->scheduled_at)->format('Y-m-d'),
                    'time' => Carbon::parse($notification->scheduled_at)->format('H:i'),
                    'Message' => $notification->message,
                    'users' => $users,
                    'is_sent' => $notification->is_sent,
                ];
            }

            return response()->json([
                'events' => $events
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching scheduled notifications for current year: ' . $e->getMessage());
            return response()->json([
                'error' => 'Something went wrong while fetching notification data.'
            ], 500);
        }
    }
}

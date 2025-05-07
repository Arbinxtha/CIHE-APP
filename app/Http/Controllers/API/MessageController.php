<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\DM_Basecontroller;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Events\MessageSent;


class MessageController extends DM_Basecontroller
{


    /**
     * Steps to send the messages:
     * 1. Get the list of student and admin(for teacher)
     * 2. Get list of the teachers and students (for admin)
     * 3. Get list of the teacher , and admin (for student )
     */

    /**
     * @OA\Get(
     *     path="/api/get-people",
     *     summary="Get group members based on authenticated user's role and groups",
     *     description="Returns users grouped by group name. Also includes admin info if requester is not an admin.",
     *     operationId="getPeople",
     *     tags={"People"},
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
     *                             "role": "lecturer",
     *                             "username": "teacher"
     *                         }
     *                     }
     *                 }
     *             ),
     *             @OA\Property(
     *                 property="admin",
     *                 type="object",
     *                 nullable=true,
     *                 example={
     *                     "id": 2,
     *                     "username": "admin"
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

     public function indexpage()
     {
         try {
             $userId = Auth::id();
             $user = Auth::user();
             $role = $user->role;
     
             // Get admin user info
             $admin = User::where('role', 'admin')->first(['id', 'username']);
     
             // If admin is logged in
             if ($role === 'admin') {
                 $students = User::where('role', 'student')->get(['id', 'role', 'username']);
                 $lecturers = User::where('role', 'lecturer')->get(['id', 'role', 'username']);
     
                 parent::saveLog('Admin fetched all students and lecturers.');
     
                 return response()->json([
                     'students' => $students,
                     'lecturers' => $lecturers
                 ]);
             }
     
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
                     'admin' => $admin
                 ]);
             }
     
             if ($role === 'student') {
                 // Lecturers in the same group(s) as the student
                 $lecturerIds = GroupMember::whereIn('group_id', $groupIds)
                     ->where('user_id', '!=', $userId)
                     ->pluck('user_id');
     
                 $lecturers = User::whereIn('id', $lecturerIds)
                     ->where('role', 'lecturer')
                     ->get(['id', 'role', 'username']);
     
                 parent::saveLog('Student fetched lecturers from same group.');
     
                 return response()->json([
                     'lecturers' => $lecturers,
                     'admin' => $admin
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
     * Get chat messages between the authenticated user and the receiver
     */
    /**
     * @OA\Get(
     *     path="/api/get-messages/{receiverId}",
     *     summary="Get all messages between authenticated user and a specific receiver",
     *     description="Fetches a chronological conversation between the authenticated user and the specified receiver.",
     *     operationId="getMessages",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="receiverId",
     *         in="path",
     *         required=true,
     *         description="ID of the user to fetch messages with",
     *         @OA\Schema(type="integer", example=4)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Messages fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="conversation",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=4),
     *                     @OA\Property(property="sender_id", type="integer", example=2),
     *                     @OA\Property(property="sender_name", type="string", example="admin"),
     *                     @OA\Property(property="receiver_id", type="integer", example=4),
     *                     @OA\Property(property="receiver_name", type="string", example="sandip"),
     *                     @OA\Property(property="content", type="string", example="Hello admin sent message to the user id 4"),
     *                     @OA\Property(property="type", type="string", example="sent"),
     *                     @OA\Property(property="timestamp", type="string", example="16 hours ago")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Failed to fetch messages."
     *             )
     *         )
     *     )
     * )
     */

    public function getMessages($receiverId)
    {
        try {
            $userId = Auth::id();

            $messages = Message::where(function ($query) use ($userId, $receiverId) {
                $query->where('sender_id', $userId)->where('receiver_id', $receiverId);
            })
                ->orWhere(function ($query) use ($userId, $receiverId) {
                    $query->where('sender_id', $receiverId)->where('receiver_id', $userId);
                })
                ->orderBy('created_at', 'asc')
                ->get();

            $formatted = $messages->map(function ($msg) use ($userId) {
                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'sender_name' => $msg->sender->username,
                    'receiver_id' => $msg->receiver_id,
                    'receiver_name' => $msg->receiver->username,
                    'content' => $msg->content,
                    'type' => $msg->sender_id == $userId ? 'sent' : 'received',
                    'timestamp' => $msg->created_at->diffForHumans(), // Human-readable timestamp
                ];
            });

            return response()->json([
                'conversation' => $formatted
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching messages: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to fetch messages.'
            ], 500);
        }
    }


   
  /**
 * @OA\Post(
 *     path="/api/send-messages",
 *     summary="Send message to multiple users if allowed (admin or shared group)",
 *     tags={"Messages"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"receiver_ids", "content"},
 *             @OA\Property(
 *                 property="receiver_ids",
 *                 type="array",
 *                 @OA\Items(type="integer"),
 *                 example={2, 5}
 *             ),
 *             @OA\Property(
 *                 property="content",
 *                 type="string",
 *                 example="Hello, this is a test message"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Messages sent successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Messages sent successfully!"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="sender_id", type="integer"),
 *                     @OA\Property(property="receiver_id", type="integer"),
 *                     @OA\Property(property="content", type="string"),
 *                     @OA\Property(property="type", type="string", example="sent"),
 *                     @OA\Property(property="timestamp", type="string")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="User not authorized to send messages to specified receivers"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Validation Error"),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={"receiver_ids": {"The receiver_ids field is required."}}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Failed to send messages"
 *     )
 * )
 */


    public function sendMessages(Request $request)
    {
        try {
            $request->validate([
                'receiver_ids' => 'required|array|min:1',
                'receiver_ids.*' => 'required|exists:users,id',
                'content' => 'required|string|max:5000',
            ]);

            $sender = Auth::user();
            $senderId = $sender->id;
            $receiverIds = $request->receiver_ids;
            $content = $request->content;

            $sentMessages = [];

            // Only fetch sender groups once
            $senderGroupIds = GroupMember::where('user_id', $senderId)->pluck('group_id');

            foreach ($receiverIds as $receiverId) {
                $canSend = false;

                // Check if the sender or receiver is an admin
                if ($sender->role === 'admin' || User::find($receiverId)->role === 'admin') {
                    $canSend = true;
                } else {
                    // If not an admin, check if they share at least one group
                    $canSend = GroupMember::where('user_id', $receiverId)
                        ->whereIn('group_id', $senderGroupIds)
                        ->exists();
                }

                if (!$canSend) {
                    continue; // Skip if not allowed
                }

                // Save message
                $message = Message::create([
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'content' => $content,
                ]);
                broadcast(new MessageSent($message));

                parent::saveLog("User {$senderId} sent a message to user {$receiverId}");

                $sentMessages[] = [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'receiver_id' => $message->receiver_id,
                    'content' => $message->content,
                    'type' => 'sent',
                    'time' => $message->created_at,
                    'timestamp' => $message->created_at->diffForHumans(), // Human-readable timestamp
                ];
            }

            if (empty($sentMessages)) {
                return response()->json([
                    'message' => 'No messages were sent. You are not allowed to message the selected users.'
                ], 403);
            }

            return response()->json([
                'message' => 'Messages sent successfully!',
                'data' => $sentMessages
            ], 201);
        } catch (ValidationException $ex) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $ex->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error sending messages: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to send messages.'
            ], 500);
        }
    }
}

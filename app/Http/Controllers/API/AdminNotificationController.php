<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\CustomNotification;

class AdminNotificationController extends Controller
{
    /**
     * Send a custom notification to a list of users.
     */
    public function send(Request $request)
    {
        $validatedData = $request->validate([
            'user_ids'  => 'required|array',
            'user_ids.*'=> 'exists:users,id',
            'title'     => 'required|string',
            'message'   => 'required|string',
            'action_url'=> 'nullable|url',
        ]);

        $notification = new CustomNotification(
            $validatedData['title'],
            $validatedData['message'],
            $validatedData['action_url'] ?? null
        );

        $users = User::whereIn('id', $validatedData['user_ids'])->get();
        foreach ($users as $user) {
            $user->notify($notification);
        }

        return response()->json(['message' => 'Notifications sent successfully']);
    }
}

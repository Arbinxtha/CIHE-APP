<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Display the specified user.
     */

    /**
     * @OA\Get(
     *     path="/api/view-profile",
     *     tags={"Profile Management"},
     *     summary="Retrieve a user by token  ",
     *     description="This endpoint retrieves a user by.. If the user is not found or does not meet the criteria, an error message is returned.",
     *     parameters={
     *         @OA\Parameter(
     *             name="id",
     *             in="path",
     *             required=true,
     *             description="The ID of the user to retrieve",
     *             @OA\Schema(type="integer", example=1)
     *         )
     *     },
     *     responses={
     *         @OA\Response(
     *             response=200,
     *             description="User retrieved successfully",
     *             @OA\JsonContent(
     *                 type="object",
     *                 properties={
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="sanjaya@gmail.com"),
     *                     @OA\Property(property="email_verified_at", type="string", example=null),
     *                     @OA\Property(property="role", type="string", example="student"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="created_at", type="string", example="2025-04-01T15:21:23.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", example="2025-04-01T17:06:49.000000Z"),
     *                     @OA\Property(property="username", type="string", example="sanjaya"),
     *                     @OA\Property(property="first_name", type="string", example="Sanjaya"),
     *                     @OA\Property(property="last_name", type="string", example="Thapa")
     *                 }
     *             )
     *         ),
     *         @OA\Response(
     *             response=404,
     *             description="User not found",
     *             @OA\JsonContent(
     *                 @OA\Property(property="error", type="string", example="Not Found")
     *             )
     *         ),
     *         @OA\Response(
     *             response=500,
     *             description="Something Error occurred",
     *             @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="Something Error occurred")
     *             )
     *         )
     *     }
     * )
     */
    public function show()
    {
        try {
            $user = User::findOrFail(Auth::id());
            return response()->json($user);
        } catch (ModelNotFoundException $mnfe) {
            return response()->json([
                'message' => 'User not found',
            ], 401);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Something Error occurred',
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/update-profile",
     *     tags={"Profile Management"},
     *     summary="Update a user by token",
     *     description="This endpoint updates a user by their token. If the user is not found or does not meet the criteria, an error message is returned.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     type="object",
     *                     properties={
     *                         @OA\Property(property="username", type="string", maxLength=50, example="sanjaya"),
     *                         @OA\Property(property="password", type="string", minLength=6, example="newpassword"),
     *                         @OA\Property(property="first_name", type="string", maxLength=50, example="Sanjaya"),
     *                         @OA\Property(property="last_name", type="string", maxLength=50, example="Thapa"),
     *                         @OA\Property(property="email", type="string", format="email", example="sanjaya@gmail.com")
     *                     }
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="message", type="string", example="User updated successfully"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     properties={
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="email", type="string", example="sanjaya@gmail.com"),
     *                         @OA\Property(property="role", type="string", example="student"),
     *                         @OA\Property(property="status", type="string", example="active"),
     *                         @OA\Property(property="created_at", type="string", example="2025-04-01T15:21:23.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", example="2025-04-05T18:10:14.000000Z"),
     *                         @OA\Property(property="username", type="string", example="sanjaya"),
     *                         @OA\Property(property="first_name", type="string", example="Sanjaya"),
     *                         @OA\Property(property="last_name", type="string", example="Thapa")
     *                     }
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found or invalid role",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Not Found")
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


    public function update(Request $request)
    {
        try {
            $user = User::findOrFail(Auth::id());
            $validatedData = $request->validate([
                'username'   => 'sometimes|required|max:50|unique:users,username,' . $user->id,
                'password'   => 'sometimes|required|min:6',
                'first_name' => 'nullable|string|max:50',
                'last_name'  => 'nullable|string|max:50',
                'email'      => 'sometimes|required|email|unique:users,email,' . $user->id,
            ]);

            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            }

            $user->update($validatedData);

            return response()->json([
                'message' => 'User updated successfully',
                'user'    => $user
            ]);
        } catch (ModelNotFoundException $mnfe) {
            return response()->json([
                'message' => 'Please check the Id of the user then try again',
            ], 401);
        } catch (ValidationException $ex) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $ex->errors(),
            ], 422);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Something Error occurred',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/change-password",
     *     summary="Change user password",
     *     tags={"Profile Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"old_password","password","password_confirmation"},
     *             @OA\Property(property="old_password", type="string", example="oldpass123"),
     *             @OA\Property(property="password", type="string", example="newpass123"),
     *             @OA\Property(property="password_confirmation", type="string", example="newpass123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password changed successfully."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Old password incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The old password you provided is incorrect.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred. Please try again later.")
     *         )
     *     )
     * )
     */

    public function changepassword(Request $request)
    {
        try {
            $user = Auth::user();

            // Validate the input
            $request->validate([
                'old_password' => 'required|min:6',
                'password' => 'required|min:6|confirmed',
            ]);

            // Check if the old password matches
            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'message' => 'The old password you provided is incorrect.',
                ], 400);
            }

            // Update the password
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'message' => 'Password changed successfully.',
                'user' => $user,
            ]);
        } catch (ValidationException $ex) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $ex->errors(),
            ], 422);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'An error occurred. Please try again later.',
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Login a user (admin, lecturer, or student)
     */

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="User login and token generation",
     *     description="Allows users to log in using email and password. Upon successful login, a personal access token is returned for further authenticated requests.",
     *     requestBody={
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"email", "password"},
     *                 @OA\Property(property="email", type="string", format="email", example="sanjaya@gmail.com", description="The user's email address."),
     *                 @OA\Property(property="password", type="string", example="password123", description="The user's password.")
     *             )
     *         )
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Login successful and token generated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="token", type="string", example="5|Dslm1dYMVICFo4ay7EP8knQhamrYOYoW1tJe2Uqm42a81400"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="sanjaya@gmail.com"),
     *                 @OA\Property(property="email_verified_at", type="string", example=null),
     *                 @OA\Property(property="role", type="string", example="student"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="created_at", type="string", example="2025-04-01T15:21:23.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-04-01T17:06:49.000000Z"),
     *                 @OA\Property(property="username", type="string", example="sanjaya"),
     *                 @OA\Property(property="first_name", type="string", example="Sanjaya"),
     *                 @OA\Property(property="last_name", type="string", example="Thapa")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     )
     * )
     */

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Create a personal access token
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user
        ]);
    }

    /**
     * Logout the authenticated user
     */

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Authentication"},
     *     summary="User logout",
     *     description="Logs out the user by revoking all personal access tokens.",
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     responses={
     *         @OA\Response(
     *             response=200,
     *             description="Logged out successfully",
     *             @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="Logged out successfully")
     *             )
     *         ),
     *         @OA\Response(
     *             response=401,
     *             description="Unauthorized - Token required or invalid",
     *             @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="Unauthorized")
     *             )
     *         )
     *     }
     * )
     */

    public function logout(Request $request)
    {
        // Revoke all tokens for the user
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    /**
     * Display a listing of lecturers and students.
     */

    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     tags={"User Management"},
     *     summary="Retrieve users with roles 'lecturer' or 'student'",
     *     description="This endpoint retrieves all users whose role is either 'lecturer' or 'student'. If no such users are found, it returns a message stating 'No data found'.",
     *     responses={
     *         @OA\Response(
     *             response=200,
     *             description="List of users with roles 'lecturer' or 'student' retrieved successfully.",
     *             @OA\JsonContent(
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     properties={
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="email", type="string", example="sanjaya@gmail.com"),
     *                         @OA\Property(property="email_verified_at", type="string", example=null),
     *                         @OA\Property(property="role", type="string", example="student"),
     *                         @OA\Property(property="status", type="string", example="active"),
     *                         @OA\Property(property="created_at", type="string", example="2025-04-01T15:21:23.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", example="2025-04-01T17:06:49.000000Z"),
     *                         @OA\Property(property="username", type="string", example="sanjaya"),
     *                         @OA\Property(property="first_name", type="string", example="Sanjaya"),
     *                         @OA\Property(property="last_name", type="string", example="Thapa")
     *                     }
     *                 )
     *             )
     *         ),
     *         @OA\Response(
     *             response=404,
     *             description="No data found",
     *             @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="No data found")
     *             )
     *         )
     *     }
     * )
     */

    public function index()
    {
        // Retrieve only users with role lecturer or student.
        $users = User::whereIn('role', ['lecturer', 'student'])->get();
        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No data found'
            ], 404);
        }
        return response()->json($users);
    }

    /**
     * Store a newly created lecturer or student in storage.
     */

    /**
     * @OA\Post(
     *     path="/api/admin/users",
     *     tags={"User Management"},
     *     summary="Create a new user",
     *     description="This endpoint allows the creation of a new user with roles 'lecturer' or 'student'. The user's username, email, and password are required, with validation applied for uniqueness and format.",
     *     requestBody={
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"username", "password", "email", "role"},
     *                 @OA\Property(property="username", type="string", example="sandip", description="The user's username (unique)"),
     *                 @OA\Property(property="password", type="string", example="password123", description="The user's password (min 6 characters)"),
     *                 @OA\Property(property="first_name", type="string", example="Sanjaya", description="The user's first name"),
     *                 @OA\Property(property="last_name", type="string", example="Thapa", description="The user's last name"),
     *                 @OA\Property(property="email", type="string", format="email", example="sandip@gmail.com", description="The user's email (unique)"),
     *                 @OA\Property(property="role", type="string", enum={"lecturer", "student"}, example="student", description="The user's role")
     *             )
     *         )
     *     },
     *     responses={
     *         @OA\Response(
     *             response=201,
     *             description="User created successfully",
     *             @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="User created successfully"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     properties={
     *                         @OA\Property(property="id", type="integer", example=4),
     *                         @OA\Property(property="username", type="string", example="sandip"),
     *                         @OA\Property(property="first_name", type="string", example="Sanjaya"),
     *                         @OA\Property(property="last_name", type="string", example="Thapa"),
     *                         @OA\Property(property="email", type="string", example="sandip@gmail.com"),
     *                         @OA\Property(property="role", type="string", example="student"),
     *                         @OA\Property(property="created_at", type="string", example="2025-04-05T18:07:22.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", example="2025-04-05T18:07:22.000000Z")
     *                     }
     *                 )
     *             )
     *         ),
     *         @OA\Response(
     *             response=422,
     *             description="Validation Error",
     *             @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="Validation Error"),
     *                 @OA\Property(property="errors", type="object", additionalProperties={}),
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

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'username'   => 'required|unique:users,username|max:50',
                'password'   => 'required|min:6',
                'first_name' => 'nullable|string|max:50',
                'last_name'  => 'nullable|string|max:50',
                'email'      => 'required|email|unique:users,email',
                'role'       => ['required', Rule::in(['lecturer', 'student'])],
            ]);

            // Hash the password before saving.
            $validatedData['password'] = Hash::make($validatedData['password']);

            $user = User::create($validatedData);

            return response()->json([
                'message' => 'User created successfully',
                'user'    => $user
            ], 201);
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
     * Display the specified user.
     */

    /**
     * @OA\Get(
     *     path="/api/admin/users/{id}",
     *     tags={"User Management"},
     *     summary="Retrieve a user by ID",
     *     description="This endpoint retrieves a user by their ID. It ensures the user has a role of 'lecturer' or 'student'. If the user is not found or does not meet the criteria, an error message is returned.",
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
     *             description="User not found or invalid role",
     *             @OA\JsonContent(
     *                 @OA\Property(property="error", type="string", example="Not Found")
     *             )
     *         ),
     *         @OA\Response(
     *             response=401,
     *             description="Invalid user ID",
     *             @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="Please check the Id of the user then try again")
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

    public function show($id)
    {
        try {
            $user = User::findOrFail($id);

            // Ensure only lecturer or student accounts can be managed.
            if (!in_array($user->role, ['lecturer', 'student'])) {
                return response()->json(['error' => 'Not Found'], 404);
            }

            return response()->json($user);
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
     * Update the specified user in storage.
     */

    /**
     * @OA\Put(
     *     path="/api/admin/users/{id}",
     *     tags={"User Management"},
     *     summary="Update a user by ID",
     *     description="This endpoint updates a user by their ID. It ensures the user has a role of 'lecturer' or 'student'. If the user is not found or does not meet the criteria, an error message is returned.",
     *     parameters={
     *         @OA\Parameter(
     *             name="id",
     *             in="path",
     *             required=true,
     *             description="The ID of the user to update",
     *             @OA\Schema(type="integer", example=1)
     *         )
     *     },
     *     requestBody={
     *         @OA\RequestBody(
     *             required=true,
     *             content={
     *                 @OA\MediaType(
     *                     mediaType="application/json",
     *                     @OA\Schema(
     *                         type="object",
     *                         properties={
     *                             @OA\Property(property="username", type="string", maxLength=50, example="sanjaya"),
     *                             @OA\Property(property="password", type="string", minLength=6, example="newpassword"),
     *                             @OA\Property(property="first_name", type="string", maxLength=50, example="Sanjaya"),
     *                             @OA\Property(property="last_name", type="string", maxLength=50, example="Thapa"),
     *                             @OA\Property(property="email", type="string", format="email", example="sanjaya@gmail.com"),
     *                             @OA\Property(property="role", type="string", enum={"lecturer", "student"}, example="student")
     *                         }
     *                     )
     *                 )
     *             }
     *         )
     *     },
     *     responses={
     *         @OA\Response(
     *             response=200,
     *             description="User updated successfully",
     *             @OA\JsonContent(
     *                 type="object",
     *                 properties={
     *                     @OA\Property(property="message", type="string", example="User updated successfully"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         properties={
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="email", type="string", example="sanjaya@gmail.com"),
     *                             @OA\Property(property="role", type="string", example="student"),
     *                             @OA\Property(property="status", type="string", example="active"),
     *                             @OA\Property(property="created_at", type="string", example="2025-04-01T15:21:23.000000Z"),
     *                             @OA\Property(property="updated_at", type="string", example="2025-04-05T18:10:14.000000Z"),
     *                             @OA\Property(property="username", type="string", example="sanjaya"),
     *                             @OA\Property(property="first_name", type="string", example="Sanjaya"),
     *                             @OA\Property(property="last_name", type="string", example="Thapa")
     *                         }
     *                     )
     *                 }
     *             )
     *         ),
     *         @OA\Response(
     *             response=404,
     *             description="User not found or invalid role",
     *             @OA\JsonContent(
     *                 @OA\Property(property="error", type="string", example="Not Found")
     *             )
     *         ),
     *         @OA\Response(
     *             response=422,
     *             description="Validation Error",
     *             @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="Validation Error"),
     *                 @OA\Property(property="errors", type="object")
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

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            if (!in_array($user->role, ['lecturer', 'student'])) {
                return response()->json(['error' => 'Not Found'], 404);
            }

            $validatedData = $request->validate([
                'username'   => 'sometimes|required|max:50|unique:users,username,' . $user->id,
                'password'   => 'sometimes|required|min:6',
                'first_name' => 'nullable|string|max:50',
                'last_name'  => 'nullable|string|max:50',
                'email'      => 'sometimes|required|email|unique:users,email,' . $user->id,
                'role'       => ['sometimes', 'required', Rule::in(['lecturer', 'student'])],
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
     * Remove the specified user from storage.
     */

    /**
     * @OA\Delete(
     *     path="/api/admin/users/{id}",
     *     tags={"User Management"},
     *     summary="Delete a user by ID",
     *     description="This endpoint deletes a user by their ID. It ensures that the user has a role of 'lecturer' or 'student'. If the user is not found or does not meet the criteria, an error message is returned.",
     *     parameters={
     *         @OA\Parameter(
     *             name="id",
     *             in="path",
     *             required=true,
     *             description="The ID of the user to delete",
     *             @OA\Schema(type="integer", example=1)
     *         )
     *     },
     *     responses={
     *         @OA\Response(
     *             response=200,
     *             description="User deleted successfully",
     *             @OA\JsonContent(
     *                 type="object",
     *                 properties={
     *                     @OA\Property(property="message", type="string", example="User deleted successfully")
     *                 }
     *             )
     *         ),
     *         @OA\Response(
     *             response=404,
     *             description="User not found or invalid role",
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
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            if (!in_array($user->role, ['lecturer', 'student'])) {
                return response()->json(['error' => 'Not Found'], 404);
            }
            $user->delete();
            return response()->json(['message' => 'User deleted successfully']);
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

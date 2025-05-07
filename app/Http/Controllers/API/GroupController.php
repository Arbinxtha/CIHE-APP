<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Course;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class GroupController extends DM_Basecontroller
{
    /**
     * Display a listing of groups.
     */
    /**
     * @OA\Get(
     *     path="/api/admin/groups",
     *     tags={"Admin - Groups"},
     *     summary="Get list of all groups",
     *     description="Retrieve all groups along with their associated course and group members. Admin access required.",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of groups retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="course_id", type="integer", example=2),
     *                 @OA\Property(property="group_name", type="string", example="BCA 2019"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-07T15:44:37.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-07T15:44:37.000000Z"),
     *                 @OA\Property(
     *                     property="course",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Bachelor in Computer Application")
     *                 ),
     *                 @OA\Property(
     *                     property="members",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(
     *                             property="user",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="first_name", type="string", example="Sanjaya"),
     *                             @OA\Property(property="last_name", type="string", example="Ghimire")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No data found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No data found")
     *         )
     *     )
     * )
     */

    public function index()
    {
        parent::saveLog('Viewed the group list.');
        // Retrieve all groups with their associated course and members
        $groups = Group::with(['course:id,name', 'members.user'])->get();
        if ($groups->isEmpty()) {
            return response()->json([
                'message' => 'No data found'
            ], 404);
        }
        return response()->json($groups);
    }

    /**
     * Store a newly created group.
     */
    /**
     * @OA\Post(
     *     path="/api/admin/groups",
     *     tags={"Admin - Groups"},
     *     summary="Create a new group",
     *     description="Create a group by providing course_id and group_name. Admin access required.",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id", "group_name"},
     *             @OA\Property(property="course_id", type="integer", example=2),
     *             @OA\Property(property="group_name", type="string", example="BCA 2019")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Group created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Group created successfully"),
     *             @OA\Property(
     *                 property="group",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="course_id", type="integer", example=2),
     *                 @OA\Property(property="group_name", type="string", example="BCA 2019"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-07T16:31:46.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-07T16:31:46.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation Error"),
     *             @OA\Property(
     *property="errors",
     *type="object",
     * additionalProperties=@OA\Property(
     *    type="array",
     *   @OA\Items(type="string", example="The course_id field is required.")
     *)
     *)

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

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'course_id'  => 'required|exists:courses,id',
                'group_name' => 'required|string|max:255',
            ]);

            $group = Group::create($validatedData);
            parent::saveLog('Created a new group named ' . $validatedData['group_name'] . '.');
            return response()->json([
                'message' => 'Group created successfully',
                'group'   => $group
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
     * Display the specified group.
     */
    /**
     * @OA\Get(
     *     path="/api/admin/groups/{id}",
     *     tags={"Admin - Groups"},
     *     summary="Get specific group details",
     *     description="Fetches a group with its associated course and members by ID. Admin access required.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Group ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=2),
     *             @OA\Property(property="group_name", type="string", example="BCA 2019"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-07T15:44:37.000000Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-07T15:44:37.000000Z"),
     *             @OA\Property(
     *                 property="course",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Bachelor in Computer Application")
     *             ),
     *             @OA\Property(
     *                 property="members",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="user", type="object", 
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="first_name", type="string", example="Sanjaya"),
     *                         @OA\Property(property="last_name", type="string", example="Shrestha")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid Group ID",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please check the Id of the user then try again")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something Error occurred")
     *         )
     *     )
     * )
     */

    public function show($id)
    {
        try {
            $group = Group::with(['course:id,name', 'members.user'])->findOrFail($id);
            parent::saveLog('Viewed details of  the group ' . $group->group_name . '.');
            return response()->json($group);
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
     * Update the specified group.
     */
    /**
     * @OA\Put(
     *     path="/api/admin/groups/{id}",
     *     tags={"Admin - Groups"},
     *     summary="Update group details",
     *     description="Updates the specified group's name and course ID. Only admins are allowed.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Group ID to update",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"group_name", "course_id"},
     *             @OA\Property(property="group_name", type="string", example="BCA 2019"),
     *             @OA\Property(property="course_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Group updated successfully"),
     *             @OA\Property(
     *                 property="group",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="course_id", type="integer", example=2),
     *                 @OA\Property(property="group_name", type="string", example="BCA 2019"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-07T15:44:37.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-07T15:44:37.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Group not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please check the Id of the user then try again")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation Error"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="group_name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The group name field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something Error occurred")
     *         )
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        try {
            $group = Group::findOrFail($id);

            $validatedData = $request->validate([
                'group_name' => 'required|required|string|max:255',
                'course_id'  => 'required|required|exists:courses,id',
            ]);

            $group->update($validatedData);
            parent::saveLog('Updated the group ' . $group->group_name . '.');

            return response()->json([
                'message' => 'Group updated successfully',
                'group'   => $group
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
     * Remove the specified group.
     */
    /**
     * @OA\Delete(
     *     path="/api/admin/groups/{id}",
     *     tags={"Admin - Groups"},
     *     summary="Delete a group",
     *     description="Deletes a specific group by ID. Only accessible by admins.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Group ID to delete",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Group deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Group not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please check the Id of the user then try again")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something Error occurred")
     *         )
     *     )
     * )
     */

    public function destroy($id)
    {
        try {
            $group = Group::findOrFail($id);
            $group->delete();
            parent::saveLog('Deleted the group ' . $group->group_name . '.');
            return response()->json(['message' => 'Group deleted successfully']);
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

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GroupMemberController extends DM_Basecontroller
{
    /**
     * Display a listing of members in a specific group.
     */
    /**
 * @OA\Get(
 *     path="/api/admin/groups/{group_id}/members",
 *     tags={"Admin - Groups - Members"},
 *     summary="List members of a group",
 *     description="Retrieves a list of members in a specific group along with their user details.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="group_id",
 *         in="path",
 *         required=true,
 *         description="Group ID to fetch members for",
 *         @OA\Schema(type="integer", example=2)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of members in the group",
 *         @OA\JsonContent(
 *             @OA\Property(property="members", type="array", @OA\Items(
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="group_id", type="integer"),
 *                 @OA\Property(property="user_id", type="integer"),
 *                 @OA\Property(property="created_at", type="string", format="date-time"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time"),
 *                 @OA\Property(property="user", type="object", 
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="first_name", type="string")
 *                 )
 *             )),
 *             @OA\Property(property="group_name", type="string", example="BCA 2019")
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
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Something Error occurred")
 *         )
 *     )
 * )
 */

    public function index($group_id)
    {
        // Ensure the group exists
        try {
            $group = Group::findOrFail($group_id);

            // Retrieve group members along with user details
            $members = GroupMember::with('user:id,first_name')->where('group_id', $group_id)->get();
            parent::saveLog('Viewed the group members list.');

            if ($members->isEmpty()) {
                return response()->json([
                    'message' => 'No data found'
                ], 404);
            }
            return response()->json([
                'members' => $members,
                'group_name' => $group->group_name
            ]);
        } catch (ModelNotFoundException $mnfe) {
            return response()->json([
                'message' => 'Please check the Id of the user then try again',
            ], 401);
        } catch (\Exception $ex) {
            Log::info($ex->getMessage());
            return response()->json([
                'message' => 'Something Error occurred',
            ], 500);
        }
    }

    /**
     * Store a newly created group member in the specified group.
     */
    /**
 * @OA\Post(
 *     path="/api/admin/groups/{group_id}/members",
 *     tags={"Admin - Groups - Members"},
 *     summary="Add a member to a group",
 *     description="Adds a member to a specific group. The user cannot be added if they are already a member.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="group_id",
 *         in="path",
 *         required=true,
 *         description="Group ID to add the member to",
 *         @OA\Schema(type="integer", example=2)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"user_id"},
 *             @OA\Property(property="user_id", type="integer", example=2)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Group member added successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Group member added successfully"),
 *             @OA\Property(property="member", type="object", 
 *                 @OA\Property(property="id", type="integer", example=3),
 *                 @OA\Property(property="user_id", type="integer", example=2),
 *                 @OA\Property(property="group_id", type="integer", example=2),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-07T17:28:04.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-07T17:28:04.000000Z")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=409,
 *         description="User is already a member of this group",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="User is already a member of this group")
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
 *         response=401,
 *         description="Group or User not found",
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

    public function store(Request $request, $group_id)
    {
        try {
            // Validate group existence and user_id in request
            $group = Group::findOrFail($group_id);

            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            // Check if the user is already a member of the group
            if (GroupMember::where('group_id', $group_id)
                ->where('user_id', $validatedData['user_id'])
                ->exists()
            ) {
                return response()->json(['error' => 'User is already a member of this group'], 409);
            }
            parent::saveLog('Added  a member to the group ' . $group->group_name . '.');
            $validatedData['group_id'] = $group_id;
            $groupMember = GroupMember::create($validatedData);
            $user = User::where('id', $validatedData['user_id'])->first();
            $user->notify(new \App\Notifications\CustomNotification(
                'Added to Group',
                'You have been added to a new group.',
            ));


            return response()->json([
                'message' => 'Group member added successfully',
                'member'  => $groupMember
            ], 201);
        } catch (ValidationException $ex) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $ex->errors(),
            ], 422);
        } catch (ModelNotFoundException $ex) {
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
     * Display the specified group member.
     */
    // public function show($group_id, $user_id)
    // {
    //     try {

    //         $groupMember = GroupMember::where('group_id', $group_id)
    //             ->where('user_id', $user_id)
    //             ->firstOrFail();

    //         parent::saveLog('Viewed  a group member ' . $groupMember->group->group_name . '.');
    //         return response()->json([
    //             'message' => 'Group member fetched successfully',
    //             'member'  => $groupMember
    //         ], 200);
    //     } catch (ModelNotFoundException $ex) {
    //         return response()->json([
    //             'message' => 'Group member not found',
    //         ], 404);
    //     } catch (\Exception $ex) {
    //         return response()->json([
    //             'message' => 'Something Error occurred',
    //         ], 500);
    //     }
    // }


    /**
     * Update the specified group member.
     */
    // public function update(Request $request, $group_id, $user_id)
    // {
    //     try {
    //         $groupMember = GroupMember::where('group_id', $group_id)
    //             ->where('user_id', $user_id)
    //             ->firstOrFail();

    //         $validatedData = $request->validate([
    //             'group_id' => 'sometimes|exists:groups,id',
    //         ]);
    //         parent::saveLog('Updated  a group member ' . $groupMember->group->group_name . '.');

    //         $groupMember->update($validatedData);

    //         return response()->json([
    //             'message' => 'Group member updated successfully',
    //             'member'  => $groupMember
    //         ], 200);
    //     } catch (ValidationException $ex) {
    //         return response()->json([
    //             'message' => 'Validation Error',
    //             'errors'  => $ex->errors(),
    //         ], 422);
    //     } catch (ModelNotFoundException $ex) {
    //         return response()->json([
    //             'message' => 'Group member not found',
    //         ], 404);
    //     } catch (\Exception $ex) {
    //         return response()->json([
    //             'message' => 'Something Error occurred',
    //         ], 500);
    //     }
    // }


    /**
     * Remove the specified group member.
     */
    /**
 * @OA\Delete(
 *     path="/api/admin/groups/{group_id}/members/{id}",
 *     tags={"Admin - Groups - Members"},
 *     summary="Remove a member from a group",
 *     description="Removes a specific member from a group. The group and member must exist.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="group_id",
 *         in="path",
 *         required=true,
 *         description="Group ID from which the member will be removed",
 *         @OA\Schema(type="integer", example=2)
 *     ),
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Member ID to be removed from the group",
 *         @OA\Schema(type="integer", example=4)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Group member removed successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Group member removed successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Group or Member not found",
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

    public function destroy($group_id, $id)
    {
        try {
            // Verify the group exists
            $group = Group::findOrFail($group_id);

            $groupMember = GroupMember::where('group_id', $group_id)->findOrFail($id);
            parent::saveLog('Deleted  a member from the group ' . $group->group_name . '.');

            $groupMember->delete();

            return response()->json(['message' => 'Group member removed successfully']);
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
     * Add multiple members to a specific group.
     */
    /**
 * @OA\Post(
 *     path="/api/admin/groups/{group_id}/members/batch/store",
 *     tags={"Admin - Groups - Members"},
 *     summary="Add multiple members to a group",
 *     description="Adds multiple users to a specific group. Ensures that users are not already members of the group.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="group_id",
 *         in="path",
 *         required=true,
 *         description="Group ID to which the members will be added",
 *         @OA\Schema(type="integer", example=2)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         description="List of user IDs to be added to the group",
 *         @OA\JsonContent(
 *             required={"user_ids"},
 *             @OA\Property(
 *                 property="user_ids",
 *                 type="array",
 *                 @OA\Items(type="integer", example=1)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Members added successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Members added successfully"),
 *             @OA\Property(
 *                 property="added_members",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="group_id", type="integer", example=2),
 *                     @OA\Property(property="user_id", type="integer", example=1),
 *                     @OA\Property(property="first_name", type="string", example="Sanjaya")
 *                 )
 *             )
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
 *         response=401,
 *         description="Group or User not found",
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

    public function storeMultiple(Request $request, $group_id)
    {
        try {
            // Ensure the group exists
            $group = Group::findOrFail($group_id);

            $validatedData = $request->validate([
                'user_ids'   => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            $addedMembers = [];
            foreach ($validatedData['user_ids'] as $user_id) {
                // Check if the user is already a member of the group
                $exists = GroupMember::where('group_id', $group_id)
                    ->where('user_id', $user_id)
                    ->exists();
                if (!$exists) {
                    $member = GroupMember::create([
                        'group_id' => $group_id,
                        'user_id'  => $user_id,
                    ]);

                    $user = User::findorfail($user_id);

                    // Add the member and their first_name to the addedMembers array
                    $addedMembers[] = [
                        'group_id'    => $group_id,
                        'user_id'     => $user_id,
                        'first_name'  => $user->first_name,  // Add first_name
                    ];
                }
            }

            return response()->json([
                'message'        => 'Members added successfully',
                'added_members'  => $addedMembers,
            ], 201);

            parent::saveLog('Added multiple members from the group ' . $group->group_name . '.');

        } catch (ValidationException $ex) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $ex->errors(),
            ], 422);
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
     * Delete multiple members from a specific group.
     */
    /**
 * @OA\DELETE(
 *     path="/api/admin/groups/{group_id}/members/batch/store",
 *     tags={"Admin - Groups - Members"},
 *     summary="Add multiple members to a group",
 *     description="Adds multiple users to a specific group. Ensures that users are not already members of the group.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="group_id",
 *         in="path",
 *         required=true,
 *         description="Group ID to which the members will be added",
 *         @OA\Schema(type="integer", example=2)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         description="List of user IDs to be added to the group",
 *         @OA\JsonContent(
 *             required={"user_ids"},
 *             @OA\Property(
 *                 property="user_ids",
 *                 type="array",
 *                 @OA\Items(type="integer", example=1)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Members added successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Members added successfully"),
 *             @OA\Property(
 *                 property="added_members",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="group_id", type="integer", example=2),
 *                     @OA\Property(property="user_id", type="integer", example=1),
 *                     @OA\Property(property="first_name", type="string", example="Sanjaya")
 *                 )
 *             )
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
 *         response=401,
 *         description="Group or User not found",
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

    public function deleteMultiple(Request $request, $group_id)
    {
        try {
            // Ensure the group exists
            $group = Group::findOrFail($group_id);

            $validatedData = $request->validate([
                'user_ids'   => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            $deletedCount = 0;
            foreach ($validatedData['user_ids'] as $user_id) {
                $member = GroupMember::where('group_id', $group_id)
                    ->where('user_id', $user_id)
                    ->first();
                if ($member) {
                    $member->delete();
                    $deletedCount++;
                }
            }
            parent::saveLog('Deleted multiple members from the group ' . $group->group_name . '.');

            return response()->json([
                'message' => $deletedCount . ' member(s) deleted successfully',
            ]);
        } catch (ValidationException $ex) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $ex->errors(),
            ], 422);
        } catch (ModelNotFoundException $mnfe) {
            Log::info($mnfe->getMessage());
            return response()->json([
                'message' => 'Please check the Id of the user then try again',
            ], 401);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Something Error occurred',
            ], 500);
        }
    }
}

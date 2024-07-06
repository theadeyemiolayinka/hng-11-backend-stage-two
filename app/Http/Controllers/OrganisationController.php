<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUserToOrganisationRequest;
use App\Http\Requests\CreateOrganisationRequest;
use App\Http\Resources\OrganisationResource;
use App\Models\Organisation;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrganisationController extends Controller
{
    /**
     * Get all organisations for a user
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $organisations = array_map(function ($o) {
            return new OrganisationResource($o);
        }, json_decode(json_encode($user->organisations->toArray()), false));

        return response()->json([
            'status' => 'success',
            'message' => 'Organisations found',
            'data' => [
                'organisations' => $organisations
            ],
        ], 200);
    }

    /**
     * Get an organisation by its ID
     * @param mixed $orgId
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function show($orgId)
    {
        $user = auth()->user();
        try {
            $organisation = Organisation::find($orgId);
            if (!$organisation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organisation not found',
                ], 404);
            }
            if (!auth()->user()->can('view', $organisation)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Organisation found',
                'data' => new OrganisationResource($organisation),
            ], 200);
        } catch (\Throwable $th) {
            Log::error('[OrganisationController@show] Error:' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Organisation not found',
            ], 404);
        }
    }

    /**
     * Create a new organisation
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function store(CreateOrganisationRequest $request)
    {
        try {
            $organisation = Organisation::create([
                'orgId' => Str::uuid()->toString(),
                'name' => $request->name,
                'description' => $request->description,
            ]);

            $organisation->users()->attach(auth()->user()->userId);

            return response()->json([
                'status' => 'success',
                'message' => 'Organisation created successfully',
                'data' => new OrganisationResource($organisation),
            ], 201);
        } catch (\Throwable $th) {
            Log::error('[OrganisationController@store] Error:' . $th->getMessage());
            return response()->json([
                'status' => 'Bad Request',
                'message' => 'Client error',
                'statusCode' => 400
            ], 400);
        }
    }

    /**
     * Add a user to an organisation
     * @param \Illuminate\Http\Request $request
     * @param mixed $orgId
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addUser(Request $request, $orgId)
    {
        try {
            /**
             * This is used instead of AddUserToOrganisationRequest class as PGSQL does not support sting-uuid comparison.
             */
            $validated = Validator::make($request->all(), [
                'userId' => 'required|string|exists:users,userId',
            ]);
            if ($validated->fails()) {
                throw new Exception('User not found');
            }
        } catch (\Throwable $th) {
            return response()->json([
                'errors' => [
                    [
                        'field' => 'userId',
                        'message' => 'User not found',
                    ]
                ]
            ], 422);
        }
        try {

            $organisation = Organisation::find($orgId);
            if (!$organisation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organisation not found',
                ], 404);
            }

            $user = User::where('userId', $request->userId)->first();
            $organisation->users()->attach($user->userId);

            return response()->json([
                'status' => 'success',
                'message' => 'User added to organisation successfully',
            ], 200);
        } catch (\Throwable $th) {
            Log::error('[OrganisationController@addUser] Error:' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Organisation not found',
            ], 404);
        }
    }
}

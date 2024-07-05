<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Organisation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function register(RegisterUserRequest $request)
    {
        try {
            $user = User::create([
                'userId' => Str::uuid()->toString(),
                'firstName' => $request->firstName,
                'lastName' => $request->lastName,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
            ]);

            $organisation = Organisation::create([
                'orgId' => Str::uuid()->toString(),
                'name' => "{$request->firstName}'s Organisation",
            ]);

            $user->organisations()->attach($organisation->orgId);

            $token = $user->createToken('Personal Access Token')->accessToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful',
                'data' => [
                    'accessToken' => $token,
                    'user' => new UserResource($user),
                ]
            ], 201);
        } catch (\Throwable $th) {
            Log::error('[AuthController@register] Error:' . $th->getMessage());
            return response()->json([
                'status' => 'Bad request',
                'message' => 'Registration unsuccessful',
                'statusCode' => 400
            ], 400);
        }
    }

    /**
     * Login a user
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function login(LoginUserRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'status' => 'Bad request',
                'message' => 'Authentication failed',
                'statusCode' => 401,
            ], 401);
        }

        try {

            $user = auth()->user();
            $token = $user->createToken('Personal Access Token @' . now()->toString())->accessToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'accessToken' => $token,
                    'user' => new UserResource($user),
                ]
            ], 200);
        } catch (\Throwable $th) {
            Log::error('[AuthController@login] Error:' . $th->getMessage());
            return response()->json([
                'status' => 'Bad request',
                'message' => 'Authentication failed',
                'statusCode' => 401,
            ], 401);
        }
    }

    /**
     * Return a user by ID
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'User found',
                'data' => new UserResource($user),
            ], 200);
        } catch (\Throwable $th) {
            Log::error('[AuthController@show] Error:' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }
    }
}

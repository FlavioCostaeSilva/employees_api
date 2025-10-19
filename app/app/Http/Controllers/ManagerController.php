<?php

namespace App\Http\Controllers;

use App\Models\Manager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ManagerController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:managers',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $manager = Manager::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $token = $manager->createToken('api-token')->plainTextToken;

            return response()->json([
                'user' => $manager,
                'token' => $token,
            ], 201);
        } catch (ValidationException $exception) {
            return $this->jsonResponse(message: $exception, status: Response::HTTP_BAD_REQUEST);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $manager = Manager::where('email', $request->email)->first();

            if (!$manager || !Hash::check($request->password, $manager->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Wrong credentials'],
                ]);
            }

            $token = $manager->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'manager' => $manager,
            ]);
        } catch (ValidationException $exception) {
            return $this->jsonResponse(message: $exception, status: Response::HTTP_BAD_REQUEST);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logout has been succeeded',
            ], 200);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $manager = $request->user();

            return response()->json([
                'manager' => [
                    'id' => $manager->id,
                    'name' => $manager->name,
                    'email' => $manager->email,
                    'email_verified_at' => $manager->email_verified_at,
                    'created_at' => $manager->created_at,
                    'updated_at' => $manager->updated_at,
                ],
                'total_employees' => $manager->employees()->count(),
            ], 200);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}

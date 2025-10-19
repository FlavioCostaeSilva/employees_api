<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $validated['cpf'] = preg_replace('/\D/', '', $validated['cpf']);

            $employee = $request->user()->employees()->create($validated);

            return response()->json([
                'message' => 'Employee created with success',
                'employee' => $employee,
            ], 201);
        } catch (ValidationException $exception) {
            return $this->jsonResponse(message: $exception, status: Response::HTTP_BAD_REQUEST);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $employee = $request->user()->employees()->findOrFail($id);

            return response()->json($employee);
        } catch (ValidationException $exception) {
            return $this->jsonResponse(message: $exception, status: Response::HTTP_BAD_REQUEST);
        } catch (ModelNotFoundException $exception) {
            return $this->jsonResponse(message: $exception, status: Response::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function update(UpdateEmployeeRequest $request, $id): JsonResponse
    {
        try {
            $employee = $request->user()->employees()->findOrFail($id);

            $validated = $request->validated();

            $employee->update($validated);

            return response()->json([
                'message' => 'Employee updated with success',
                'employee' => $employee->fresh(),
            ]);
        } catch (ValidationException $exception) {
            return $this->jsonResponse(message: $exception, status: Response::HTTP_BAD_REQUEST);
        } catch (ModelNotFoundException $exception) {
            return $this->jsonResponse(message: $exception, status: Response::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $employee = $request->user()->employees()->findOrFail($id);

            $employeeName = $employee->name;
            $employee->delete();

            return response()->json([
                'message' => "Employee {$employeeName} deleted with success",
            ]);
        } catch (ValidationException $exception) {
            return $this->jsonResponse(message: $exception, status: Response::HTTP_BAD_REQUEST);
        } catch (ModelNotFoundException $exception) {
            return $this->jsonResponse(message: $exception, status: Response::HTTP_NOT_FOUND);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}

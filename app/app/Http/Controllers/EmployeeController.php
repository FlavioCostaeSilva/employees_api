<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $managerId = $request->user()->id;
            $cacheKey = "manager:{$managerId}:employees";
            $cacheTTL = 3600;

            $employees = Cache::remember($cacheKey, $cacheTTL, function () use ($request) {
                return $request->user()
                    ->employees()
                    ->get();
            });

            $data = new \stdClass();
            $data->count = $employees->count();
            $data->registers = $employees->toArray();

            return $this->jsonResponse(data: $data);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $employee = $request->user()->employees()->create($validated);

            $managerId = $request->user()->id;
            Cache::forget("manager:{$managerId}:employees");

            return $this->jsonResponse(
                data: [
                    'employee' => $employee,
                ],
                message: 'Employee created with success',
                status: Response::HTTP_CREATED
            );
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
            Validator::make(['id' => $id], [
                'id' => 'required|numeric',
            ])->validate();

            $employee = $request->user()->employees()->findOrFail($id);

            return $this->jsonResponse(
                data: $employee
            );
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

            $managerId = $request->user()->id;
            Cache::forget("manager:{$managerId}:employees");

            return $this->jsonResponse(
                data: [
                    'employee' => $employee->fresh(),
                ],
                message: 'Employee updated with success',
            );
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
            Validator::make(['id' => $id], [
                'id' => 'required|numeric',
            ])->validate();

            $employee = $request->user()->employees()->findOrFail($id);

            $employeeName = $employee->name;
            $employee->delete();

            $managerId = $request->user()->id;
            Cache::forget("manager:{$managerId}:employees");

            return $this->jsonResponse(
                message: "Employee {$employeeName} deleted with success",
            );
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

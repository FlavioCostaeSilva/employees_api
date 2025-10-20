<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEmployeeCsv;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EmployeeUploadCSVController extends Controller
{
    public function uploadCsv(Request $request): JsonResponse
    {
        try {
            Validator::make($request->all(), [
                'csv_file' => 'required|file|mimes:csv,txt|max:2048',
            ])->validate();

            $jobId = Str::uuid()->toString();
            $file = $request->file('csv_file');

            $uploadPath = storage_path('app/csv_imports');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fileName = $jobId . '_' . $file->getClientOriginalName();
            $filePath = $uploadPath . '/' . $fileName;
            $file->move($uploadPath, $fileName);

            ProcessEmployeeCsv::dispatch($request->user(), $filePath, $jobId);

            return $this->jsonResponse(message: 'The file will be processed. Check the /employees later');
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

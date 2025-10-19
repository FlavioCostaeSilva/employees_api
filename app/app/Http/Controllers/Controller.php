<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Exception;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function jsonResponse(
        object|array|null $data = null,
        string|array|null|Exception $message = null,
        int $status = Response::HTTP_OK
    ): JsonResponse
    {
        $payload = new \stdClass();
        $payload->data = $data;
        $payload->message = $message;

        if ($message instanceof ValidationException) {
            $payload->message = $message->validator->getMessageBag()->all();
        }
        if ($message instanceof Exception) {
            $payload->message = $message->getMessage();
        }
        return response()->json($payload, $status);
    }
}

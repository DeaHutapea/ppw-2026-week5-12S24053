<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /** Respons sukses standar (default 200 OK). */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Permintaan berhasil diproses.',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /** 201 Created + header Location. */
    protected function createdResponse(
        mixed $data,
        string $locationUrl,
        string $message = 'Resource baru berhasil dibuat.'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201)
            ->header('Location', $locationUrl);
    }

    /** 204 No Content (tanpa body). */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /** Galat standar RFC 7807 (application/problem+json). */
    protected function problemResponse(
        string $title,
        string $detail,
        int $status,
        string $errorSlug = 'general-error',
        array $errors = []
    ): JsonResponse {
        $problem = [
            'type' => url("/docs/errors/{$errorSlug}"),
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => request()->getRequestUri(),
        ];

        if (! empty($errors)) {
            $problem['errors'] = $errors;
        }

        return response()
            ->json($problem, $status)
            ->header('Content-Type', 'application/problem+json');
    }
}
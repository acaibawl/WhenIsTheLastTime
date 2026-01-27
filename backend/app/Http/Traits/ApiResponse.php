<?php

declare(strict_types=1);

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * Return a success response.
     *
     * @param array<string, mixed>|null $data
     * @param array<string, mixed> $headers
     */
    protected function successResponse(
        ?array $data = null,
        string $message = 'Success',
        int $code = Response::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code, $headers);
    }

    /**
     * Return an error response.
     *
     * @param array<string, mixed>|null $errors
     * @param array<string, mixed> $headers
     */
    protected function errorResponse(
        string $message = 'Error',
        int $code = Response::HTTP_BAD_REQUEST,
        ?array $errors = null,
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code, $headers);
    }

    /**
     * Return a created response.
     *
     * @param array<string, mixed>|null $data
     */
    protected function createdResponse(?array $data = null, string $message = 'Created'): JsonResponse
    {
        return $this->successResponse($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Return a no content response.
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Return a not found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Return a forbidden response.
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Return a validation error response.
     *
     * @param array<string, mixed> $errors
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Return a success response with meta information.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    protected function successResponseWithMeta(
        array $data,
        int $code = Response::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], $code, $headers);
    }
}

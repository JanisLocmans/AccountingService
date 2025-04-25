<?php

declare(strict_types=1);

namespace App\Trait;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Trait ResponseFormatterTrait
 *
 * Provides standardized methods for formatting API responses.
 * Ensures consistent response structure across all API endpoints.
 */
trait ResponseFormatterTrait
{
    protected SerializerInterface $serializer;

    /**
     * Creates a standardized success response.
     *
     * @param mixed $data The data to include in the response
     * @param array<string> $groups Serialization groups to apply
     * @param int $status HTTP status code
     * @return JsonResponse
     */
    protected function createSuccessResponse(
        mixed $data = null,
        array $groups = [],
        int $status = Response::HTTP_OK
    ): JsonResponse {
        return $this->createJsonResponse([
            'success' => true,
            'data' => $data
        ], $status, $groups);
    }

    /**
     * Creates a standardized error response.
     *
     * @param string $message Error message
     * @param int $status HTTP status code
     * @param array<string, string> $errors Detailed error information
     * @return JsonResponse
     */
    protected function createErrorResponse(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return $this->createJsonResponse($response, $status);
    }

    /**
     * Creates a JSON response with the given data.
     *
     * @param mixed $data The data to serialize
     * @param int $status HTTP status code
     * @param array<string> $groups Serialization groups to apply
     * @param array<string, string> $headers Additional response headers
     * @return JsonResponse
     */
    protected function createJsonResponse(
        mixed $data,
        int $status = Response::HTTP_OK,
        array $groups = [],
        array $headers = []
    ): JsonResponse {
        $context = empty($groups) ? [] : ['groups' => $groups];
        $json = $this->serializer->serialize($data, 'json', $context);
        
        return new JsonResponse($json, $status, $headers, true);
    }
}

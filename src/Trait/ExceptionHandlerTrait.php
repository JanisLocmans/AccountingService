<?php

declare(strict_types=1);

namespace App\Trait;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait ExceptionHandlerTrait
 *
 * Provides methods for handling exceptions in a consistent way across controllers.
 */
trait ExceptionHandlerTrait
{
    use ResponseFormatterTrait;

    // The logger property is now provided by the ApiController class

    /**
     * Handle exceptions in a standardized way.
     *
     * @param \Throwable $exception The exception to handle
     * @param bool $logException Whether to log the exception
     * @return JsonResponse
     */
    protected function handleException(\Throwable $exception, bool $logException = true): JsonResponse
    {
        // Log the exception if requested and logger is available
        if ($logException && $this->logger !== null) {
            $this->logger->error('Exception occurred: {message}', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        // Map exception types to appropriate HTTP status codes and messages
        if ($exception instanceof \InvalidArgumentException) {
            return $this->createErrorResponse(
                $exception->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($exception instanceof \RuntimeException) {
            return $this->createErrorResponse(
                $exception->getMessage(),
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        // Default case for unexpected exceptions
        return $this->createErrorResponse(
            'An unexpected error occurred',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}

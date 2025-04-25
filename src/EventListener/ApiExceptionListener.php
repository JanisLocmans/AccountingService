<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Trait\ResponseFormatterTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Converts exceptions to standardized JSON responses for API routes.
 */
#[AsEventListener]
class ApiExceptionListener
{
    use ResponseFormatterTrait;

    public function __construct(
        SerializerInterface $serializer,
        private LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // Only handle exceptions for API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        // Log the exception
        $this->logger->error('API Exception: {message}', [
            'message' => $exception->getMessage(),
            'exception' => $exception,
            'trace' => $exception->getTraceAsString(),
        ]);

        // Create an appropriate response based on the exception type
        $response = $this->createExceptionResponse($exception);

        // Set the response on the event
        $event->setResponse($response);
    }

    private function createExceptionResponse(\Throwable $exception): JsonResponse
    {
        // Handle HTTP exceptions (which already have status codes)
        if ($exception instanceof HttpExceptionInterface) {
            return $this->createErrorResponse(
                $exception->getMessage(),
                $exception->getStatusCode()
            );
        }

        // Handle specific exception types

        if ($exception instanceof \InvalidArgumentException) {
            return $this->createErrorResponse(
                $exception->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        }

        // Default to 500 Internal Server Error for unexpected exceptions
        return $this->createErrorResponse(
            'An unexpected error occurred',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Trait\EntityFinderTrait;
use App\Trait\ExceptionHandlerTrait;
use App\Trait\PaginationTrait;
use App\Trait\ResponseFormatterTrait;
use App\Trait\ValidationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Base API controller with common functionality for all API endpoints.
 *
 * This abstract class provides standardized methods for handling API responses,
 * validation, entity lookup, pagination, and exception handling.
 */
abstract class ApiController extends AbstractController
{
    use ResponseFormatterTrait;
    use ValidationTrait;
    use EntityFinderTrait;
    use PaginationTrait;
    use ExceptionHandlerTrait;

    /**
     * ApiController constructor.
     *
     * @param SerializerInterface $serializer For serializing/deserializing data
     * @param ValidatorInterface $validator For validating DTOs
     * @param EntityManagerInterface $entityManager For entity operations
     * @param LoggerInterface|null $logger For logging exceptions
     */
    public function __construct(
        protected SerializerInterface $serializer,
        protected ValidatorInterface $validator,
        protected EntityManagerInterface $entityManager,
        protected ?LoggerInterface $logger = null
    ) {
    }

    /**
     * @deprecated Use createSuccessResponse() instead
     */
    protected function success($data = null, array $groups = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->createSuccessResponse($data, $groups, $status);
    }

    /**
     * @deprecated Use createErrorResponse() instead
     */
    protected function error(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        array $errors = []
    ): JsonResponse {
        return $this->createErrorResponse($message, $status, $errors);
    }

    /**
     * @deprecated Use createJsonResponse() instead
     */
    protected function json(
        mixed $data,
        int $status = Response::HTTP_OK,
        array $groups = [],
        array $headers = []
    ): JsonResponse {
        return $this->createJsonResponse($data, $status, $groups, $headers);
    }
}

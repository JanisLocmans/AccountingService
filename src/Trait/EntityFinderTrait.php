<?php

declare(strict_types=1);

namespace App\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait EntityFinderTrait
 *
 * Provides methods for finding entities and handling "not found" scenarios.
 */
trait EntityFinderTrait
{
    use ResponseFormatterTrait;

    protected EntityManagerInterface $entityManager;

    /**
     * Finds an entity by ID with proper error handling.
     *
     * @template T of object
     * @param class-string<T> $entityClass The entity class
     * @param int $id The entity ID
     * @param string $entityName Human-readable entity name for error messages
     * @return array{0: T|null, 1: JsonResponse|null} Tuple containing either the entity or an error response
     */
    protected function findEntityById(string $entityClass, int $id, string $entityName): array
    {
        /** @var EntityRepository<T> $repository */
        $repository = $this->entityManager->getRepository($entityClass);
        $entity = $repository->find($id);

        if ($entity === null) {
            return [null, $this->createErrorResponse(
                sprintf('%s not found with ID: %d', $entityName, $id),
                Response::HTTP_NOT_FOUND
            )];
        }

        return [$entity, null];
    }

    /**
     * Validates that a string ID is a valid integer and returns it as an int.
     *
     * @param string $id The ID to validate
     * @return array{0: int|null, 1: JsonResponse|null} Tuple containing either the validated ID or an error response
     */
    protected function validateId(string $id): array
    {
        if (!ctype_digit($id)) {
            return [null, $this->createErrorResponse(
                'Invalid ID format. ID must be an integer.',
                Response::HTTP_BAD_REQUEST
            )];
        }

        return [(int) $id, null];
    }
}

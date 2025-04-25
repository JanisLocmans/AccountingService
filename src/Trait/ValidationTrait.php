<?php

declare(strict_types=1);

namespace App\Trait;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Trait ValidationTrait
 *
 * Provides methods for validating request data and handling validation errors.
 */
trait ValidationTrait
{
    use ResponseFormatterTrait;

    protected ValidatorInterface $validator;
    protected SerializerInterface $serializer;

    /**
     * Deserializes and validates a request into a DTO.
     *
     * @template T
     * @param Request $request The HTTP request
     * @param class-string<T> $dtoClass The DTO class to deserialize into
     * @return array{0: T|null, 1: JsonResponse|null} Tuple containing either the validated DTO or an error response
     */
    protected function validateRequest(Request $request, string $dtoClass): array
    {
        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                $dtoClass,
                'json'
            );
            
            $violations = $this->validator->validate($dto);
            
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
                
                return [null, $this->createErrorResponse(
                    'Validation failed',
                    Response::HTTP_BAD_REQUEST,
                    $errors
                )];
            }
            
            return [$dto, null];
        } catch (\Throwable $e) {
            return [null, $this->createErrorResponse(
                'Invalid request format: ' . $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            )];
        }
    }
}

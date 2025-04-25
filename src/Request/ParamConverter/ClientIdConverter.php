<?php

declare(strict_types=1);

namespace App\Request\ParamConverter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Converts client ID parameters in routes, handling invalid formats gracefully.
 */
class ClientIdConverter implements ValueResolverInterface
{
    public function __construct()
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Only process 'id' parameters that are expected to be integers
        if ($argument->getName() !== 'id' || $argument->getType() !== 'int') {
            return [];
        }

        // Only process API routes
        $route = $request->attributes->get('_route');
        $path = $request->getPathInfo();
        if (!$route || !str_starts_with($path, '/api')) {
            return [];
        }

        $id = $request->attributes->get('id');

        // If the ID is already an integer, no conversion needed
        if (is_int($id)) {
            return [$id];
        }

        // Try to convert the ID to an integer
        if (is_string($id) && ctype_digit($id)) {
            return [(int)$id];
        }

        // If we get here, the ID is invalid
        throw new BadRequestHttpException('Invalid ID format. ID must be an integer.');
    }
}

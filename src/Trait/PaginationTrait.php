<?php

declare(strict_types=1);

namespace App\Trait;

use Symfony\Component\HttpFoundation\Request;

/**
 * Trait PaginationTrait
 *
 * Provides methods for handling pagination parameters in API requests.
 */
trait PaginationTrait
{
    /**
     * Extract and normalize pagination parameters from a request.
     *
     * @param Request $request The HTTP request
     * @param int $defaultLimit Default number of items per page
     * @param int $maxLimit Maximum allowed items per page
     * @return array{offset: int, limit: int} Normalized pagination parameters
     */
    protected function getPaginationParams(
        Request $request,
        int $defaultLimit = 10,
        int $maxLimit = 100
    ): array {
        $offset = max(0, $request->query->getInt('offset', 0));
        $limit = min($maxLimit, max(1, $request->query->getInt('limit', $defaultLimit)));
        
        return [
            'offset' => $offset,
            'limit' => $limit,
        ];
    }

    /**
     * Create a pagination metadata object.
     *
     * @param int $offset Current offset
     * @param int $limit Current limit
     * @param int $total Total number of items
     * @return array{offset: int, limit: int, total: int, pages: int, current_page: int} Pagination metadata
     */
    protected function createPaginationMetadata(int $offset, int $limit, int $total): array
    {
        $pages = $limit > 0 ? (int) ceil($total / $limit) : 0;
        $currentPage = $limit > 0 ? (int) floor($offset / $limit) + 1 : 1;
        
        return [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $total,
            'pages' => $pages,
            'current_page' => $currentPage,
        ];
    }
}

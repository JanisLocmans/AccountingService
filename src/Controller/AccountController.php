<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/accounts')]
class AccountController extends ApiController
{
    /**
     * AccountController constructor.
     *
     * @param SerializerInterface $serializer For serializing/deserializing data
     * @param ValidatorInterface $validator For validating DTOs
     * @param EntityManagerInterface $entityManager For entity operations
     * @param TransactionRepository $transactionRepository Repository for transaction entities
     * @param LoggerInterface|null $logger For logging exceptions
     */
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        private TransactionRepository $transactionRepository,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($serializer, $validator, $entityManager, $logger);
    }

    #[Route('/{id}/transactions', name: 'api_account_transactions', methods: ['GET'])]
    #[OA\Get(
        path: '/api/accounts/{id}/transactions',
        operationId: 'getAccountTransactions',
        description: 'Returns a paginated list of transactions for the specified account',
        summary: 'Get transaction history for an account',
        tags: ['Accounts'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Account ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'offset',
                description: 'Pagination offset',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 0)
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Number of items per page (max 100)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10, maximum: 100, minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'transactions',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/Transaction')
                                ),
                                new OA\Property(
                                    property: 'pagination',
                                    properties: [
                                        new OA\Property(property: 'offset', type: 'integer', example: 0),
                                        new OA\Property(property: 'limit', type: 'integer', example: 10),
                                        new OA\Property(property: 'total', type: 'integer', example: 42)
                                    ],
                                    type: 'object'
                                )
                            ],
                            type: 'object'
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Account not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getAccountTransactions(int $id, Request $request): Response
    {
        try {
            // Find the account
            [$account, $errorResponse] = $this->findEntityById(Account::class, $id, 'Account');
            if ($errorResponse) {
                return $errorResponse;
            }

            // Get pagination parameters
            $pagination = $this->getPaginationParams($request, 10, 100);
            $offset = $pagination['offset'];
            $limit = $pagination['limit'];

            // Get transactions with pagination
            $transactions = $this->transactionRepository->findByAccountWithPagination($account, $offset, $limit);
            $total = $this->transactionRepository->countByAccount($account);

            // Create pagination metadata
            $paginationMetadata = $this->createPaginationMetadata($offset, $limit, $total);

            return $this->createSuccessResponse([
                'transactions' => $transactions,
                'pagination' => $paginationMetadata
            ], ['transaction:read']);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}

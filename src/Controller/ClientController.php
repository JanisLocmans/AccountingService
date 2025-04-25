<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Repository\AccountRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/clients')]
class ClientController extends ApiController
{
    /**
     * ClientController constructor.
     *
     * @param SerializerInterface $serializer For serializing/deserializing data
     * @param ValidatorInterface $validator For validating DTOs
     * @param EntityManagerInterface $entityManager For entity operations
     * @param ClientRepository $clientRepository Repository for client entities
     * @param AccountRepository $accountRepository Repository for account entities
     * @param LoggerInterface|null $logger For logging exceptions
     */
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        private ClientRepository $clientRepository,
        private AccountRepository $accountRepository,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($serializer, $validator, $entityManager, $logger);
    }

    #[Route('/{id}/accounts', name: 'api_client_accounts', methods: ['GET'])]
    #[OA\Get(
        path: '/api/clients/{id}/accounts',
        operationId: 'getClientAccounts',
        description: 'Returns a list of accounts for the specified client',
        summary: 'Get accounts for a client',
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Client ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
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
                            ref: '#/components/schemas/Client'
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Client not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getClientAccounts(int $id): Response
    {
        try {
            [$client, $errorResponse] = $this->findEntityById(Client::class, $id, 'Client');
            if ($errorResponse) {
                return $errorResponse;
            }

            return $this->createSuccessResponse($client, ['client:read']);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}

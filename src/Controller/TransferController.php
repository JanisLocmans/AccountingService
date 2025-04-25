<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\TransferRequest;
use App\Service\TransferServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/transfers')]
class TransferController extends ApiController
{
    /**
     * TransferController constructor.
     *
     * @param SerializerInterface $serializer For serializing/deserializing data
     * @param ValidatorInterface $validator For validating DTOs
     * @param EntityManagerInterface $entityManager For entity operations
     * @param TransferServiceInterface $transferService Service for transferring funds
     * @param LoggerInterface|null $logger For logging exceptions
     */
    public function __construct(
        SerializerInterface                       $serializer,
        ValidatorInterface                        $validator,
        EntityManagerInterface                    $entityManager,
        private readonly TransferServiceInterface $transferService,
        ?LoggerInterface                          $logger = null
    ) {
        parent::__construct($serializer, $validator, $entityManager, $logger);
    }

    #[Route('', name: 'api_transfer_funds', methods: ['POST'])]
    #[OA\Post(
        path: '/api/transfers',
        operationId: 'transferFunds',
        description: 'Transfers funds from one account to another with currency conversion if needed',
        summary: 'Transfer funds between accounts',
        requestBody: new OA\RequestBody(
            description: 'Transfer request details',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TransferRequest')
        ),
        tags: ['Transfers'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Funds transferred successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/Transaction'
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid input',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 404,
                description: 'Account not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 503,
                description: 'Service unavailable',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function transferFunds(Request $request): Response
    {
        try {
            // Validate the request
            [$transferRequest, $errorResponse] = $this->validateRequest($request, TransferRequest::class);
            if ($errorResponse) {
                return $errorResponse;
            }

            // Process the transfer
            $transaction = $this->transferService->transfer(
                $transferRequest->getSourceAccountId(),
                $transferRequest->getDestinationAccountId(),
                $transferRequest->getAmount(),
                $transferRequest->getCurrency(),
                $transferRequest->getDescription()
            );

            return $this->createSuccessResponse($transaction, ['transaction:read'], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}

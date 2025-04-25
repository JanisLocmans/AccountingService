<?php

namespace App\Tests\Controller;

use App\Controller\TransferController;
use App\DTO\TransferRequest;
use App\Entity\Transaction;
use App\Service\TransferServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransferControllerTest extends TestCase
{
    private TransferController $controller;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;
    private TransferServiceInterface $transferService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->transferService = $this->createMock(TransferServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new TransferController(
            $this->serializer,
            $this->validator,
            $this->entityManager,
            $this->transferService,
            $this->logger
        );
    }

    public function testTransferFundsWithValidData(): void
    {
        // Create a mock request
        $requestContent = '{"sourceAccountId":1,"destinationAccountId":2,"amount":100,"currency":"USD","description":"Test transfer"}';
        $request = new Request([], [], [], [], [], [], $requestContent);

        // Create a mock transfer request DTO
        $transferRequest = new TransferRequest();
        $transferRequest->setSourceAccountId(1);
        $transferRequest->setDestinationAccountId(2);
        $transferRequest->setAmount(100);
        $transferRequest->setCurrency('USD');
        $transferRequest->setDescription('Test transfer');

        // Create a mock transaction
        $transaction = new Transaction();
        $transaction->setId(1);
        $transaction->setAmount(100);
        $transaction->setCurrency('USD');
        $transaction->setDescription('Test transfer');

        // Configure the serializer mock
        $this->serializer->method('deserialize')
            ->willReturn($transferRequest);
        $this->serializer->method('serialize')
            ->willReturn('{"success":true,"data":{"id":1,"amount":100,"currency":"USD"}}');

        // Configure the validator mock
        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Configure the transfer service mock
        $this->transferService->method('transfer')
            ->with(1, 2, 100, 'USD', 'Test transfer')
            ->willReturn($transaction);

        // Call the controller method
        $response = $this->controller->transferFunds($request);

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":true,"data":{"id":1,"amount":100,"currency":"USD"}}', $response->getContent());
    }

    public function testTransferFundsWithInvalidData(): void
    {
        // Create a mock request
        $requestContent = '{"sourceAccountId":1,"destinationAccountId":1,"amount":-100,"currency":"XYZ"}';
        $request = new Request([], [], [], [], [], [], $requestContent);

        // Create a mock transfer request DTO
        $transferRequest = new TransferRequest();
        $transferRequest->setSourceAccountId(1);
        $transferRequest->setDestinationAccountId(1);
        $transferRequest->setAmount(-100);
        $transferRequest->setCurrency('XYZ');

        // Create mock validation errors
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Source and destination accounts cannot be the same',
                '',
                [],
                $transferRequest,
                'destinationAccountId',
                1
            ),
            new ConstraintViolation(
                'Amount must be positive',
                '',
                [],
                $transferRequest,
                'amount',
                -100
            )
        ]);

        // Configure the serializer mock
        $this->serializer->method('deserialize')
            ->willReturn($transferRequest);
        $this->serializer->method('serialize')
            ->willReturn('{"success":false,"message":"Validation failed","errors":{"destinationAccountId":"Source and destination accounts cannot be the same","amount":"Amount must be positive"}}');

        // Configure the validator mock
        $this->validator->method('validate')
            ->willReturn($violations);

        // Call the controller method
        $response = $this->controller->transferFunds($request);

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"Validation failed","errors":{"destinationAccountId":"Source and destination accounts cannot be the same","amount":"Amount must be positive"}}', $response->getContent());
    }

    public function testTransferFundsWithInvalidJson(): void
    {
        // Create a mock request with invalid JSON
        $requestContent = '{invalid json}';
        $request = new Request([], [], [], [], [], [], $requestContent);

        // Configure the serializer mock to throw an exception
        $this->serializer->method('deserialize')
            ->willThrowException(new \Exception('Invalid JSON'));
        $this->serializer->method('serialize')
            ->willReturn('{"success":false,"message":"Invalid request format: Invalid JSON"}');

        // Call the controller method
        $response = $this->controller->transferFunds($request);

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"Invalid request format: Invalid JSON"}', $response->getContent());
    }

    public function testTransferFundsWithServiceException(): void
    {
        // Create a mock request
        $requestContent = '{"sourceAccountId":1,"destinationAccountId":2,"amount":100,"currency":"USD","description":"Test transfer"}';
        $request = new Request([], [], [], [], [], [], $requestContent);

        // Create a mock transfer request DTO
        $transferRequest = new TransferRequest();
        $transferRequest->setSourceAccountId(1);
        $transferRequest->setDestinationAccountId(2);
        $transferRequest->setAmount(100);
        $transferRequest->setCurrency('USD');
        $transferRequest->setDescription('Test transfer');

        // Configure the serializer mock
        $this->serializer->method('deserialize')
            ->willReturn($transferRequest);
        $this->serializer->method('serialize')
            ->willReturn('{"success":false,"message":"Insufficient funds"}');

        // Configure the validator mock
        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Configure the transfer service mock to throw an exception
        $this->transferService->method('transfer')
            ->willThrowException(new \InvalidArgumentException('Insufficient funds'));

        // Call the controller method
        $response = $this->controller->transferFunds($request);

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"Insufficient funds"}', $response->getContent());
    }
}

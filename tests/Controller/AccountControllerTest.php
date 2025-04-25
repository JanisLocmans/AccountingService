<?php

namespace App\Tests\Controller;

use App\Controller\AccountController;
use App\Entity\Account;
use App\Entity\Client;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AccountControllerTest extends TestCase
{
    private AccountController $controller;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;
    private TransactionRepository $transactionRepository;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->transactionRepository = $this->createMock(TransactionRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new AccountController(
            $this->serializer,
            $this->validator,
            $this->entityManager,
            $this->transactionRepository,
            $this->logger
        );
    }

    public function testGetAccountTransactionsWithValidId(): void
    {
        // Create a mock account
        $client = new Client();
        $client->setId(1);
        $client->setName('Test Client');

        $account = new Account();
        $account->setId(1);
        $account->setAccountNumber('ACC001');
        $account->setCurrency('USD');
        $account->setBalance(1000);
        $account->setClient($client);

        // Create a mock request
        $request = new Request();
        $request->query->set('offset', '0');
        $request->query->set('limit', '10');

        // Configure the repository mocks
        $accountRepository = $this->createMock(\App\Repository\AccountRepository::class);
        $this->entityManager->method('getRepository')
            ->willReturn($accountRepository);
        $accountRepository->method('find')
            ->with(1)
            ->willReturn($account);

        // Configure the transaction repository mock
        $this->transactionRepository->method('findByAccountWithPagination')
            ->willReturn([]);
        $this->transactionRepository->method('countByAccount')
            ->willReturn(0);

        // Configure the serializer mock
        $this->serializer->method('serialize')
            ->willReturn('{"success":true,"data":{"transactions":[],"pagination":{"offset":0,"limit":10,"total":0,"pages":0,"current_page":1}}}');

        // Call the controller method
        $response = $this->controller->getAccountTransactions('1', $request);

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":true,"data":{"transactions":[],"pagination":{"offset":0,"limit":10,"total":0,"pages":0,"current_page":1}}}', $response->getContent());
    }

    public function testGetAccountTransactionsWithInvalidIdFormat(): void
    {
        // Create a mock request
        $request = new Request();

        // Configure the serializer mock
        $this->serializer->method('serialize')
            ->willReturn('{"success":false,"message":"Invalid ID format. ID must be an integer."}');

        // Call the controller method with an invalid ID
        $response = $this->controller->getAccountTransactions('abc', $request);

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"Invalid ID format. ID must be an integer."}', $response->getContent());
    }

    public function testGetAccountTransactionsWithNonExistentId(): void
    {
        // Create a mock request
        $request = new Request();

        // Configure the repository mocks
        $accountRepository = $this->createMock(\App\Repository\AccountRepository::class);
        $this->entityManager->method('getRepository')
            ->willReturn($accountRepository);
        $accountRepository->method('find')
            ->with(999)
            ->willReturn(null);

        // Configure the serializer mock
        $this->serializer->method('serialize')
            ->willReturn('{"success":false,"message":"Account not found with ID: 999"}');

        // Call the controller method
        $response = $this->controller->getAccountTransactions('999', $request);

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"Account not found with ID: 999"}', $response->getContent());
    }

    public function testGetAccountTransactionsWithException(): void
    {
        // Create a mock request
        $request = new Request();

        // Configure the repository mock to throw an exception
        $this->entityManager->method('getRepository')
            ->willThrowException(new \RuntimeException('Database error'));

        // Configure the serializer mock
        $this->serializer->method('serialize')
            ->willReturn('{"success":false,"message":"An unexpected error occurred"}');

        // Call the controller method
        $response = $this->controller->getAccountTransactions('1', $request);

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"An unexpected error occurred"}', $response->getContent());
    }
}

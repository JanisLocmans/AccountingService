<?php

namespace App\Tests\Controller;

use App\Controller\ClientController;
use App\Entity\Client;
use App\Repository\AccountRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ClientControllerTest extends TestCase
{
    private ClientController $controller;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;
    private ClientRepository $clientRepository;
    private AccountRepository $accountRepository;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new ClientController(
            $this->serializer,
            $this->validator,
            $this->entityManager,
            $this->clientRepository,
            $this->accountRepository,
            $this->logger
        );
    }

    public function testGetClientAccountsWithValidId(): void
    {
        // Create a mock client
        $client = new Client();
        $client->setId(1);
        $client->setName('Test Client');
        $client->setEmail('test@example.com');

        // Configure the serializer mock
        $this->serializer->method('serialize')
            ->willReturn('{"success":true,"data":{"id":1,"name":"Test Client"}}');

        // Configure the repository mock to return the client
        $this->entityManager->method('getRepository')
            ->willReturn($this->clientRepository);
        $this->clientRepository->method('find')
            ->with(1)
            ->willReturn($client);

        // Call the controller method
        $response = $this->controller->getClientAccounts('1');

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":true,"data":{"id":1,"name":"Test Client"}}', $response->getContent());
    }

    public function testGetClientAccountsWithInvalidIdFormat(): void
    {
        // Configure the serializer mock
        $this->serializer->method('serialize')
            ->willReturn('{"success":false,"message":"Invalid client ID format. Client ID must be an integer."}');

        // Call the controller method with an invalid ID
        $response = $this->controller->getClientAccounts('abc');

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"Invalid client ID format. Client ID must be an integer."}', $response->getContent());
    }

    public function testGetClientAccountsWithNonExistentId(): void
    {
        // Configure the serializer mock
        $this->serializer->method('serialize')
            ->willReturn('{"success":false,"message":"Client not found with ID: 999"}');

        // Configure the repository mock to return null (client not found)
        $this->entityManager->method('getRepository')
            ->willReturn($this->clientRepository);
        $this->clientRepository->method('find')
            ->with(999)
            ->willReturn(null);

        // Call the controller method
        $response = $this->controller->getClientAccounts('999');

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"Client not found with ID: 999"}', $response->getContent());
    }

    public function testGetClientAccountsWithException(): void
    {
        // Configure the serializer mock
        $this->serializer->method('serialize')
            ->willReturn('{"success":false,"message":"An unexpected error occurred"}');

        // Configure the repository mock to throw an exception
        $this->entityManager->method('getRepository')
            ->willThrowException(new \RuntimeException('Database error'));

        // Call the controller method
        $response = $this->controller->getClientAccounts('1');

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"An unexpected error occurred"}', $response->getContent());
    }
}

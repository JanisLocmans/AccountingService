<?php

namespace App\Tests\Trait;

use App\Trait\ExceptionHandlerTrait;
use App\Trait\ResponseFormatterTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class TestExceptionHandler
{
    use ExceptionHandlerTrait;
    use ResponseFormatterTrait;

    public function __construct(
        public SerializerInterface $serializer,
        public LoggerInterface $logger
    ) {}

    public function handleExceptionPublic(\Throwable $e): Response
    {
        return $this->handleException($e);
    }
}

class ExceptionHandlerTraitTest extends TestCase
{
    private TestExceptionHandler $traitObject;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->serializer->method('serialize')->willReturn('{"success":false,"message":"Test error"}');

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->traitObject = new TestExceptionHandler($this->serializer, $this->logger);
    }

    public function testHandleExceptionWithRuntimeException(): void
    {
        $exception = new \RuntimeException('Database error');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Runtime exception: Database error', $this->anything());

        $response = $this->traitObject->handleExceptionPublic($exception);

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"Test error"}', $response->getContent());
    }

    public function testHandleExceptionWithInvalidArgumentException(): void
    {
        $exception = new \InvalidArgumentException('Invalid input');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Invalid argument exception: Invalid input', $this->anything());

        $response = $this->traitObject->handleExceptionPublic($exception);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"Test error"}', $response->getContent());
    }

    public function testHandleExceptionWithGenericException(): void
    {
        $exception = new \Exception('Unexpected error');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception: Unexpected error', $this->anything());

        $response = $this->traitObject->handleExceptionPublic($exception);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('{"success":false,"message":"Test error"}', $response->getContent());
    }
}

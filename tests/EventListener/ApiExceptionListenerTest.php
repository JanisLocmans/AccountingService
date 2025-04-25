<?php

namespace App\Tests\EventListener;

use App\EventListener\ApiExceptionListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ApiExceptionListenerTest extends TestCase
{
    private ApiExceptionListener $listener;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->serializer->method('serialize')->willReturn('{"success":false,"message":"Test error"}');

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->listener = new ApiExceptionListener($this->serializer, $this->logger);
    }

    public function testInvokeWithApiRoute(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/clients/1/accounts');

        $exception = new BadRequestHttpException('Invalid client ID');

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with('API Exception: {message}', $this->anything());

        $this->listener->__invoke($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    public function testInvokeWithNonApiRoute(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->server->set('REQUEST_URI', '/admin/dashboard');

        $exception = new \Exception('Some error');

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->logger->expects($this->never())
            ->method('error');

        $this->listener->__invoke($event);

        $this->assertNull($event->getResponse());
    }

    public function testInvokeWithHttpException(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/clients/999/accounts');

        $exception = new NotFoundHttpException('Client not found');

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->listener->__invoke($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testInvokeWithRuntimeException(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/clients/1/accounts');

        $exception = new \RuntimeException('Database connection error');

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->listener->__invoke($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testInvokeWithGenericException(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/clients/1/accounts');

        $exception = new \Exception('Unexpected error');

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->listener->__invoke($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(500, $response->getStatusCode());
    }
}

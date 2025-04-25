<?php

namespace App\Tests\Request\ParamConverter;

use App\Request\ParamConverter\ClientIdConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ClientIdConverterTest extends TestCase
{
    private ClientIdConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new ClientIdConverter();
    }

    public function testResolveWithValidIntegerId(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'api_client_accounts');
        $request->server->set('REQUEST_URI', '/api/clients/123/accounts');
        $request->attributes->set('id', '123');

        $argument = $this->createMock(ArgumentMetadata::class);
        $argument->method('getName')->willReturn('id');
        $argument->method('getType')->willReturn('int');

        $result = iterator_to_array($this->converter->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertSame(123, $result[0]);
    }

    public function testResolveWithInvalidId(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'api_client_accounts');
        $request->server->set('REQUEST_URI', '/api/clients/123abc/accounts');
        $request->attributes->set('id', '123abc');

        $argument = $this->createMock(ArgumentMetadata::class);
        $argument->method('getName')->willReturn('id');
        $argument->method('getType')->willReturn('int');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid ID format. ID must be an integer.');

        iterator_to_array($this->converter->resolve($request, $argument));
    }

    public function testResolveWithNonApiRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'app_homepage');
        $request->server->set('REQUEST_URI', '/homepage');
        $request->attributes->set('id', '123abc');

        $argument = $this->createMock(ArgumentMetadata::class);
        $argument->method('getName')->willReturn('id');
        $argument->method('getType')->willReturn('int');

        $result = iterator_to_array($this->converter->resolve($request, $argument));

        $this->assertEmpty($result);
    }

    public function testResolveWithNonIdParameter(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'api_client_accounts');
        $request->server->set('REQUEST_URI', '/api/clients/123/accounts');
        $request->attributes->set('id', '123');

        $argument = $this->createMock(ArgumentMetadata::class);
        $argument->method('getName')->willReturn('page');
        $argument->method('getType')->willReturn('int');

        $result = iterator_to_array($this->converter->resolve($request, $argument));

        $this->assertEmpty($result);
    }

    public function testResolveWithNonIntType(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'api_client_accounts');
        $request->server->set('REQUEST_URI', '/api/clients/123/accounts');
        $request->attributes->set('id', '123');

        $argument = $this->createMock(ArgumentMetadata::class);
        $argument->method('getName')->willReturn('id');
        $argument->method('getType')->willReturn('string');

        $result = iterator_to_array($this->converter->resolve($request, $argument));

        $this->assertEmpty($result);
    }
}

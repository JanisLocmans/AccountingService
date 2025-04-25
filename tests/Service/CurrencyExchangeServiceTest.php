<?php

namespace App\Tests\Service;

use App\Service\CurrencyExchangeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CurrencyExchangeServiceTest extends TestCase
{
    private $httpClient;
    private $currencyExchangeService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $exchangeRateRepository = $this->createMock(\App\Repository\ExchangeRateRepository::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $this->currencyExchangeService = new CurrencyExchangeService(
            $this->httpClient,
            $entityManager,
            $exchangeRateRepository,
            $logger
        );
    }

    public function testGetExchangeRateWithSameCurrency(): void
    {
        $rate = $this->currencyExchangeService->getExchangeRate('USD', 'USD');
        $this->assertEquals(1.0, $rate);
    }

    public function testGetExchangeRateWithDifferentCurrencies(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'rates' => [
                'EUR' => 0.85
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->anything(),
                $this->callback(function ($options) {
                    return $options['query']['base'] === 'USD';
                })
            )
            ->willReturn($response);

        $rate = $this->currencyExchangeService->getExchangeRate('USD', 'EUR');
        $this->assertEquals(0.85, $rate);
    }

    public function testGetExchangeRateWithInvalidCurrency(): void
    {
        // Create a partial mock to test the invalid currency case
        $currencyExchangeService = $this->getMockBuilder(CurrencyExchangeService::class)
            ->setConstructorArgs([
                $this->httpClient,
                $this->createMock(\Doctrine\ORM\EntityManagerInterface::class),
                $this->createMock(\App\Repository\ExchangeRateRepository::class),
                $this->createMock(\Psr\Log\LoggerInterface::class)
            ])
            ->onlyMethods(['isCurrencySupported'])
            ->getMock();

        // Configure the mock to return false for INVALID currency
        $currencyExchangeService->method('isCurrencySupported')
            ->willReturnCallback(function($currency) {
                return $currency !== 'INVALID';
            });

        $this->expectException(\InvalidArgumentException::class);
        $currencyExchangeService->getExchangeRate('USD', 'INVALID');
    }

    public function testGetExchangeRateWithApiUnavailable(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new TransportException('API unavailable'));

        $this->expectException(\RuntimeException::class);
        $this->currencyExchangeService->getExchangeRate('USD', 'EUR');
    }

    public function testGetExchangeRateWithCachedValue(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'rates' => [
                'EUR' => 0.85
            ]
        ]);

        // First call should hit the API
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        // First call
        $rate1 = $this->currencyExchangeService->getExchangeRate('USD', 'EUR');

        // Second call should use cached value
        $rate2 = $this->currencyExchangeService->getExchangeRate('USD', 'EUR');

        $this->assertEquals(0.85, $rate1);
        $this->assertEquals(0.85, $rate2);
    }

    public function testIsCurrencySupported(): void
    {
        $this->assertTrue($this->currencyExchangeService->isCurrencySupported('USD'));
        $this->assertTrue($this->currencyExchangeService->isCurrencySupported('EUR'));
        $this->assertTrue($this->currencyExchangeService->isCurrencySupported('GBP'));
        $this->assertFalse($this->currencyExchangeService->isCurrencySupported('JPY'));
        $this->assertFalse($this->currencyExchangeService->isCurrencySupported('INVALID'));
    }

    public function testFetchAndStoreAllRates(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'rates' => [
                'EUR' => 0.85,
                'GBP' => 0.75
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('beginTransaction');
        $entityManager->expects($this->atLeastOnce())
            ->method('persist');
        $entityManager->expects($this->once())
            ->method('commit');

        $exchangeRateRepository = $this->createMock(\App\Repository\ExchangeRateRepository::class);
        $exchangeRateRepository->method('hasRecentRate')
            ->willReturn(false);

        $currencyExchangeService = new CurrencyExchangeService(
            $this->httpClient,
            $entityManager,
            $exchangeRateRepository
        );

        $rates = $currencyExchangeService->fetchAndStoreAllRates('USD');

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertArrayHasKey('GBP', $rates);
        $this->assertEquals(0.85, $rates['EUR']);
        $this->assertEquals(0.75, $rates['GBP']);
    }
}

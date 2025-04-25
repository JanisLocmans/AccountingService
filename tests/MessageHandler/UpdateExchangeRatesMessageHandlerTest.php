<?php

namespace App\Tests\MessageHandler;

use App\Message\UpdateExchangeRatesMessage;
use App\MessageHandler\UpdateExchangeRatesMessageHandler;
use App\Service\CurrencyExchangeService;
use PHPUnit\Framework\TestCase;
use App\Service\ExchangeRateLogger;

class UpdateExchangeRatesMessageHandlerTest extends TestCase
{
    private UpdateExchangeRatesMessageHandler $handler;
    private CurrencyExchangeService $currencyExchangeService;
    private ExchangeRateLogger $logger;

    protected function setUp(): void
    {
        $this->currencyExchangeService = $this->createMock(CurrencyExchangeService::class);
        $this->logger = $this->createMock(ExchangeRateLogger::class);

        $this->handler = new UpdateExchangeRatesMessageHandler(
            $this->currencyExchangeService,
            $this->logger
        );
    }

    public function testInvoke(): void
    {
        // Create a message
        $message = new UpdateExchangeRatesMessage(['USD'], ['EUR', 'GBP']);

        // Configure the mock
        $this->currencyExchangeService->expects($this->once())
            ->method('fetchAndStoreAllRates')
            ->with('USD', ['EUR', 'GBP'])
            ->willReturn([
                'EUR' => 0.85,
                'GBP' => 0.75
            ]);

        // Configure the logger mock
        $this->logger->expects($this->once())
            ->method('logStart')
            ->with(['USD'], ['EUR', 'GBP']);

        $this->logger->expects($this->once())
            ->method('logSuccess')
            ->with('USD', $this->anything(), $this->anything());

        $this->logger->expects($this->once())
            ->method('logCompletion')
            ->with(1, 0, $this->anything());

        // Invoke the handler
        $this->handler->__invoke($message);
    }

    public function testInvokeWithError(): void
    {
        // Create a message
        $message = new UpdateExchangeRatesMessage(['USD'], ['EUR', 'GBP']);

        // Configure the mock to throw an exception
        $this->currencyExchangeService->expects($this->once())
            ->method('fetchAndStoreAllRates')
            ->with('USD', ['EUR', 'GBP'])
            ->willThrowException(new \RuntimeException('API error'));

        // Configure the logger mock
        $this->logger->expects($this->once())
            ->method('logStart')
            ->with(['USD'], ['EUR', 'GBP']);

        $this->logger->expects($this->once())
            ->method('logError')
            ->with('USD', $this->anything(), $this->anything());

        $this->logger->expects($this->once())
            ->method('logCompletion')
            ->with(0, 1, $this->anything());

        // Invoke the handler
        $this->handler->__invoke($message);
    }
}

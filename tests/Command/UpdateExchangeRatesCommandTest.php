<?php

namespace App\Tests\Command;

use App\Command\UpdateExchangeRatesCommand;
use App\Service\CurrencyExchangeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Psr\Log\LoggerInterface;

class UpdateExchangeRatesCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private CurrencyExchangeService $currencyExchangeService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->currencyExchangeService = $this->createMock(CurrencyExchangeService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $command = new UpdateExchangeRatesCommand(
            $this->currencyExchangeService,
            $this->logger
        );

        $application = new Application();
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    public function testExecute(): void
    {
        // Configure the mock
        $this->currencyExchangeService->expects($this->any())
            ->method('fetchAndStoreAllRates')
            ->willReturn([
                'EUR' => 0.85,
                'GBP' => 0.75
            ]);

        // Execute the command
        $this->commandTester->execute([]);

        // Assert the command output
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Exchange rates update completed', $output);
        $this->assertStringContainsString('succeeded', $output);
        $this->assertStringContainsString('failed', $output);
    }

    public function testExecuteWithError(): void
    {
        // Configure the mock to throw an exception
        $this->currencyExchangeService->expects($this->any())
            ->method('fetchAndStoreAllRates')
            ->willThrowException(new \RuntimeException('API error'));

        // We don't need to configure the logger mock for this test

        // Execute the command
        $this->commandTester->execute([]);

        // Assert the command output
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Exchange rates update completed', $output);
        $this->assertStringContainsString('0 succeeded', $output);
        $this->assertStringContainsString('failed', $output);
    }
}

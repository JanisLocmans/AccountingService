<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ExchangeRateLogger
{
    private string $logFile;

    public function __construct(
        private LoggerInterface $logger,
        string $projectDir
    ) {
        $this->logFile = $projectDir . '/var/log/exchange_rates.log';

        // Ensure log file exists
        $filesystem = new Filesystem();
        if (!$filesystem->exists($this->logFile)) {
            $filesystem->touch($this->logFile);
        }
    }

    /**
     * Log the start of an exchange rate update
     */
    public function logStart(array $baseCurrencies, array $targetCurrencies): void
    {
        $message = sprintf(
            "[%s] Starting exchange rate update - Base currencies: %s, Target currencies: %s\n",
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            implode(', ', $baseCurrencies),
            implode(', ', $targetCurrencies)
        );

        $this->writeToLogFile($message);

        $this->logger->info('Starting exchange rate update', [
            'base_currencies' => implode(', ', $baseCurrencies),
            'target_currencies' => implode(', ', $targetCurrencies),
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Log a successful exchange rate update
     */
    public function logSuccess(string $baseCurrency, array $rates, float $executionTime): void
    {
        $message = sprintf(
            "[%s] Exchange rate update successful - Base currency: %s, Rates count: %d, Execution time: %.2f ms\n",
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $baseCurrency,
            count($rates),
            round($executionTime * 1000, 2)
        );

        $this->writeToLogFile($message);

        $this->logger->info('Exchange rate update successful', [
            'base_currency' => $baseCurrency,
            'rates_count' => count($rates),
            'execution_time_ms' => round($executionTime * 1000, 2),
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Log a failed exchange rate update
     */
    public function logError(string $baseCurrency, string $errorMessage, float $executionTime): void
    {
        $message = sprintf(
            "[%s] Exchange rate update failed - Base currency: %s, Error: %s, Execution time: %.2f ms\n",
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $baseCurrency,
            $errorMessage,
            round($executionTime * 1000, 2)
        );

        $this->writeToLogFile($message);

        $this->logger->error('Exchange rate update failed', [
            'base_currency' => $baseCurrency,
            'error' => $errorMessage,
            'execution_time_ms' => round($executionTime * 1000, 2),
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Log the completion of all exchange rate updates
     */
    public function logCompletion(int $successCount, int $errorCount, float $totalExecutionTime): void
    {
        $message = sprintf(
            "[%s] Exchange rate update completed - Success count: %d, Error count: %d, Total execution time: %.2f ms\n",
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $successCount,
            $errorCount,
            round($totalExecutionTime * 1000, 2)
        );

        $this->writeToLogFile($message);

        $this->logger->info('Exchange rate update completed', [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'total_execution_time_ms' => round($totalExecutionTime * 1000, 2),
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Write a message to the log file
     */
    private function writeToLogFile(string $message): void
    {
        file_put_contents($this->logFile, $message, FILE_APPEND);
    }
}

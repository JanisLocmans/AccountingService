<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\UpdateExchangeRatesMessage;
use App\Service\CurrencyExchangeService;
use App\Service\ExchangeRateLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateExchangeRatesMessageHandler
{
    public function __construct(
        private CurrencyExchangeService $currencyExchangeService,
        private ExchangeRateLogger $logger
    ) {
    }

    public function __invoke(UpdateExchangeRatesMessage $message): void
    {
        $baseCurrencies = $message->getBaseCurrencies();
        $targetCurrencies = $message->getTargetCurrencies();

        $startTime = microtime(true);
        $this->logger->logStart($baseCurrencies, $targetCurrencies);

        $successCount = 0;
        $errorCount = 0;

        foreach ($baseCurrencies as $baseCurrency) {
            $currencyStartTime = microtime(true);

            try {
                $rates = $this->currencyExchangeService->fetchAndStoreAllRates($baseCurrency, $targetCurrencies);
                $successCount++;

                $this->logger->logSuccess(
                    $baseCurrency,
                    $rates,
                    microtime(true) - $currencyStartTime
                );
            } catch (\Exception $e) {
                $errorCount++;

                $this->logger->logError(
                    $baseCurrency,
                    $e->getMessage(),
                    microtime(true) - $currencyStartTime
                );
            }
        }

        $this->logger->logCompletion($successCount, $errorCount, microtime(true) - $startTime);
    }
}

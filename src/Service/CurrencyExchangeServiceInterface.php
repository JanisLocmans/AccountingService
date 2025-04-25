<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Interface CurrencyExchangeServiceInterface
 *
 * Defines the contract for currency exchange services.
 */
interface CurrencyExchangeServiceInterface
{
    /**
     * Get the exchange rate from one currency to another.
     *
     * @param string $fromCurrency Source currency code (e.g., 'USD')
     * @param string $toCurrency Target currency code (e.g., 'EUR')
     * @return float The exchange rate
     * 
     * @throws \InvalidArgumentException If the currencies are not supported
     * @throws \RuntimeException If the exchange rate service is unavailable
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): float;
    
    /**
     * Check if a currency is supported by the service.
     *
     * @param string $currency Currency code to check
     * @return bool True if the currency is supported
     */
    public function isCurrencySupported(string $currency): bool;
    
    /**
     * Fetch and store all exchange rates for a base currency.
     *
     * @param string $baseCurrency Base currency code
     * @param array<string> $targetCurrencies Target currency codes
     * @return array<string, float> Array of currency => rate pairs
     * 
     * @throws \InvalidArgumentException If the currencies are not supported
     * @throws \RuntimeException If the exchange rate service is unavailable
     */
    public function fetchAndStoreAllRates(string $baseCurrency, array $targetCurrencies = []): array;
}

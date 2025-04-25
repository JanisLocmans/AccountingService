<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ExchangeRate;
use App\Repository\ExchangeRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for handling currency exchange operations.
 *
 * This service provides methods for retrieving exchange rates between currencies,
 * with caching and database persistence for resilience against API outages.
 */
class CurrencyExchangeService implements CurrencyExchangeServiceInterface
{
    private const CACHE_TTL = 3600; // 1 hour in seconds
    private const DB_TTL = 86400; // 24 hours in seconds
    private const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'INR', 'RUB'];

    private array $cachedRates = [];
    private array $cachedTimestamps = [];
    private string $apiUrl;

    /**
     * CurrencyExchangeService constructor.
     *
     * @param HttpClientInterface $httpClient HTTP client for API requests
     * @param EntityManagerInterface $entityManager Entity manager for database operations
     * @param ExchangeRateRepository $exchangeRateRepository Repository for exchange rate entities
     * @param LoggerInterface|null $logger Logger for recording errors and operations
     * @param string|null $apiUrl Override for the API URL
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private ExchangeRateRepository $exchangeRateRepository,
        private ?LoggerInterface $logger = null,
        ?string $apiUrl = null
    ) {
        $this->apiUrl = $apiUrl ?? $_ENV['CURRENCY_EXCHANGE_API_URL'] ?? 'https://open.er-api.com/v6/latest';
    }

    /**
     * Check if a currency is supported
     */
    public function isCurrencySupported(string $currency): bool
    {
        return in_array(strtoupper($currency), self::SUPPORTED_CURRENCIES);
    }

    /**
     * Get exchange rate from one currency to another
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        // If currencies are the same, return 1
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        // Check if we have a cached rate that's still valid
        $cacheKey = $this->getCacheKey($fromCurrency, $toCurrency);
        if (isset($this->cachedRates[$cacheKey]) && isset($this->cachedTimestamps[$cacheKey])) {
            if (time() - $this->cachedTimestamps[$cacheKey] < self::CACHE_TTL) {
                return $this->cachedRates[$cacheKey];
            }
        }

        // Try to fetch from API
        try {
            $rate = $this->fetchExchangeRateFromApi($fromCurrency, $toCurrency);

            // Cache the result in memory
            $this->cachedRates[$cacheKey] = $rate;
            $this->cachedTimestamps[$cacheKey] = time();

            // Store in database for long-term persistence
            $this->storeRateInDatabase($fromCurrency, $toCurrency, $rate);

            return $rate;
        } catch (TransportException $e) {
            // If API is down, try to get from database
            $exchangeRate = $this->exchangeRateRepository->findLatestRate($fromCurrency, $toCurrency);

            if ($exchangeRate) {
                $rate = (float) $exchangeRate->getRate();

                // Cache the database result
                $this->cachedRates[$cacheKey] = $rate;
                $this->cachedTimestamps[$cacheKey] = time();

                return $rate;
            }

            // If we have a cached rate, use it even if it's expired
            if (isset($this->cachedRates[$cacheKey])) {
                return $this->cachedRates[$cacheKey];
            }

            // Otherwise, rethrow the exception
            throw new \RuntimeException('Currency exchange service is unavailable and no fallback data exists', 0, $e);
        }
    }

    /**
     * Fetch exchange rate from API
     */
    private function fetchExchangeRateFromApi(string $fromCurrency, string $toCurrency): float
    {
        // Check if we have a recent rate in the database to avoid unnecessary API calls
        if ($this->exchangeRateRepository->hasRecentRate($fromCurrency, $toCurrency, self::DB_TTL)) {
            $exchangeRate = $this->exchangeRateRepository->findLatestRate($fromCurrency, $toCurrency);
            if ($exchangeRate) {
                return (float) $exchangeRate->getRate();
            }
        }

        // Validate currencies
        if (!$this->isCurrencySupported($fromCurrency)) {
            throw new \InvalidArgumentException(
                "Currency {$fromCurrency} is not supported. Supported currencies: " .
                implode(', ', self::SUPPORTED_CURRENCIES)
            );
        }

        if (!$this->isCurrencySupported($toCurrency)) {
            throw new \InvalidArgumentException(
                "Currency {$toCurrency} is not supported. Supported currencies: " .
                implode(', ', self::SUPPORTED_CURRENCIES)
            );
        }

        $queryParams = [
            'base' => $fromCurrency
        ];

        $response = $this->httpClient->request('GET', $this->apiUrl, [
            'query' => $queryParams,
        ]);

        $data = $response->toArray();

        // Check if the API response is successful
        if (isset($data['success']) && $data['success'] === false) {
            $errorMsg = $data['error']['info'] ?? 'Unknown error';
            throw new \InvalidArgumentException("API Error: {$errorMsg}");
        }

        if (!isset($data['rates']) || !isset($data['rates'][$toCurrency])) {
            throw new \InvalidArgumentException("Could not get exchange rate from {$fromCurrency} to {$toCurrency}");
        }

        return (float) $data['rates'][$toCurrency];
    }

    /**
     * Store exchange rate in the database
     */
    private function storeRateInDatabase(string $fromCurrency, string $toCurrency, float $rate): void
    {
        // Check if we already have this rate
        $exchangeRate = $this->exchangeRateRepository->findLatestRate($fromCurrency, $toCurrency);

        if ($exchangeRate) {
            // Update existing rate
            $exchangeRate->setRate((string) $rate);
            $exchangeRate->setUpdatedAt(new \DateTimeImmutable());
        } else {
            // Create new rate
            $exchangeRate = new ExchangeRate();
            $exchangeRate->setBaseCurrency($fromCurrency);
            $exchangeRate->setTargetCurrency($toCurrency);
            $exchangeRate->setRate((string) $rate);
        }

        $this->entityManager->persist($exchangeRate);
        $this->entityManager->flush();
    }

    /**
     * Fetch all exchange rates for a base currency and store them
     *
     * @return array<string, float> Array of currency => rate pairs
     */
    public function fetchAndStoreAllRates(string $baseCurrency, array $targetCurrencies = []): array
    {
        try {
            // Try to fetch from API
            // Validate currency
            if (!$this->isCurrencySupported($baseCurrency)) {
                throw new \InvalidArgumentException(
                    "Currency {$baseCurrency} is not supported. Supported currencies: " .
                    implode(', ', self::SUPPORTED_CURRENCIES)
                );
            }

            $queryParams = [
                'base' => $baseCurrency
            ];

            $response = $this->httpClient->request('GET', $this->apiUrl, [
                'query' => $queryParams,
            ]);

            $data = $response->toArray();

            // Check if the API response is successful
            if (isset($data['success']) && $data['success'] === false) {
                $errorMsg = $data['error']['info'] ?? 'Unknown error';
                throw new \RuntimeException("API Error: {$errorMsg}");
            }

            if (empty($data['rates'])) {
                throw new \RuntimeException("Could not get exchange rates for {$baseCurrency}");
            }

            // Filter rates to only include supported currencies
            $rates = array_filter($data['rates'], function ($currency) {
                return $this->isCurrencySupported($currency);
            }, ARRAY_FILTER_USE_KEY);

            // Store all rates in database
            $this->entityManager->beginTransaction();

            try {
                foreach ($rates as $targetCurrency => $rate) {
                    $this->storeRateInDatabase($baseCurrency, $targetCurrency, (float) $rate);
                }

                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }

            return $rates;
        } catch (TransportException $e) {
            // If API is down, try to get from database
            $rates = $this->exchangeRateRepository->findAllRatesForBaseCurrency($baseCurrency);

            if (!empty($rates)) {
                return $rates;
            }

            throw new \RuntimeException('Currency exchange service is unavailable and no fallback data exists', 0, $e);
        }
    }

    /**
     * Generate cache key for a currency pair
     */
    private function getCacheKey(string $fromCurrency, string $toCurrency): string
    {
        return "{$fromCurrency}_{$toCurrency}";
    }
}

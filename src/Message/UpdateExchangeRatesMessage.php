<?php

declare(strict_types=1);

namespace App\Message;

class UpdateExchangeRatesMessage
{
    public function __construct(
        private array $baseCurrencies = ['USD', 'EUR', 'GBP'],
        private array $targetCurrencies = ['USD', 'EUR', 'GBP']
    ) {
    }

    public function getBaseCurrencies(): array
    {
        return $this->baseCurrencies;
    }

    public function getTargetCurrencies(): array
    {
        return $this->targetCurrencies;
    }
}

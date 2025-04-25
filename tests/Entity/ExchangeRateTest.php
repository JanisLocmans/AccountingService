<?php

namespace App\Tests\Entity;

use App\Entity\ExchangeRate;
use PHPUnit\Framework\TestCase;

class ExchangeRateTest extends TestCase
{
    private ExchangeRate $exchangeRate;

    protected function setUp(): void
    {
        $this->exchangeRate = new ExchangeRate();
    }

    public function testGettersAndSetters(): void
    {
        $date = new \DateTimeImmutable('2023-01-01');

        $this->exchangeRate->setId(1);
        $this->exchangeRate->setBaseCurrency('USD');
        $this->exchangeRate->setTargetCurrency('EUR');
        $this->exchangeRate->setRate('0.85');
        $this->exchangeRate->setCreatedAt($date);

        $this->assertEquals(1, $this->exchangeRate->getId());
        $this->assertEquals('USD', $this->exchangeRate->getBaseCurrency());
        $this->assertEquals('EUR', $this->exchangeRate->getTargetCurrency());
        $this->assertEquals('0.85', $this->exchangeRate->getRate());
        $this->assertEquals($date, $this->exchangeRate->getCreatedAt());
    }
}

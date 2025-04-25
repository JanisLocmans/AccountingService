<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ExchangeRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExchangeRate>
 *
 * @method ExchangeRate|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExchangeRate|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExchangeRate[]    findAll()
 * @method ExchangeRate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExchangeRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRate::class);
    }

    /**
     * Find the latest exchange rate for a currency pair
     */
    public function findLatestRate(string $baseCurrency, string $targetCurrency): ?ExchangeRate
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.baseCurrency = :baseCurrency')
            ->andWhere('e.targetCurrency = :targetCurrency')
            ->setParameter('baseCurrency', $baseCurrency)
            ->setParameter('targetCurrency', $targetCurrency)
            ->orderBy('e.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all exchange rates for a base currency
     *
     * @return array<string, float> Array of currency => rate pairs
     */
    public function findAllRatesForBaseCurrency(string $baseCurrency): array
    {
        $results = $this->createQueryBuilder('e')
            ->andWhere('e.baseCurrency = :baseCurrency')
            ->setParameter('baseCurrency', $baseCurrency)
            ->orderBy('e.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $rates = [];
        foreach ($results as $result) {
            $targetCurrency = $result->getTargetCurrency();
            if (!isset($rates[$targetCurrency])) {
                $rates[$targetCurrency] = (float) $result->getRate();
            }
        }

        return $rates;
    }

    /**
     * Check if we have a recent exchange rate (less than maxAgeSeconds old)
     */
    public function hasRecentRate(string $baseCurrency, string $targetCurrency, int $maxAgeSeconds = 86400): bool
    {
        $minDate = new \DateTimeImmutable("-{$maxAgeSeconds} seconds");

        $count = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.baseCurrency = :baseCurrency')
            ->andWhere('e.targetCurrency = :targetCurrency')
            ->andWhere('e.updatedAt > :minDate')
            ->setParameter('baseCurrency', $baseCurrency)
            ->setParameter('targetCurrency', $targetCurrency)
            ->setParameter('minDate', $minDate)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}

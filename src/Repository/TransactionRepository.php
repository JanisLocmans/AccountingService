<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * Find transactions for an account with pagination
     */
    public function findByAccountWithPagination(Account $account, int $offset = 0, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.sourceAccount = :account OR t.destinationAccount = :account')
            ->setParameter('account', $account)
            ->orderBy('t.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total transactions for an account
     */
    public function countByAccount(Account $account): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.sourceAccount = :account OR t.destinationAccount = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Transaction;

/**
 * Interface TransferServiceInterface
 *
 * Defines the contract for transfer services.
 */
interface TransferServiceInterface
{
    /**
     * Transfer funds between accounts.
     *
     * @param int $sourceAccountId ID of the source account
     * @param int $destinationAccountId ID of the destination account
     * @param float $amount Amount to transfer
     * @param string $currency Currency of the transfer
     * @param string|null $description Optional description of the transfer
     * @return Transaction The created transaction
     * 
     * @throws \InvalidArgumentException If the accounts are not found or the transfer is invalid
     * @throws \RuntimeException If the transfer fails due to a system error
     */
    public function transfer(
        int $sourceAccountId,
        int $destinationAccountId,
        float $amount,
        string $currency,
        ?string $description = null
    ): Transaction;
}

<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TransferRequest
{
    #[Assert\NotBlank(message: 'Source account ID is required')]
    #[Assert\Positive(message: 'Source account ID must be a positive number')]
    private int $sourceAccountId;

    #[Assert\NotBlank(message: 'Destination account ID is required')]
    #[Assert\Positive(message: 'Destination account ID must be a positive number')]
    #[Assert\NotEqualTo(
        propertyPath: 'sourceAccountId',
        message: 'Destination account cannot be the same as source account'
    )]
    private int $destinationAccountId;

    #[Assert\NotBlank(message: 'Amount is required')]
    #[Assert\Positive(message: 'Amount must be a positive number')]
    private float $amount;

    #[Assert\NotBlank(message: 'Currency is required')]
    #[Assert\Length(exactly: 3, exactMessage: 'Currency must be a 3-letter code')]
    #[Assert\Currency(message: 'Invalid currency code')]
    private string $currency;

    #[Assert\Length(max: 255, maxMessage: 'Description cannot be longer than {{ limit }} characters')]
    private ?string $description = null;

    public function getSourceAccountId(): int
    {
        return $this->sourceAccountId;
    }

    public function setSourceAccountId(int $sourceAccountId): self
    {
        $this->sourceAccountId = $sourceAccountId;
        return $this;
    }

    public function getDestinationAccountId(): int
    {
        return $this->destinationAccountId;
    }

    public function setDestinationAccountId(int $destinationAccountId): self
    {
        $this->destinationAccountId = $destinationAccountId;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = strtoupper($currency);
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
}

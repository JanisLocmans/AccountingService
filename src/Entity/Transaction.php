<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['transaction:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'outgoingTransactions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['transaction:read'])]
    private ?Account $sourceAccount = null;

    #[ORM\ManyToOne(inversedBy: 'incomingTransactions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['transaction:read'])]
    private ?Account $destinationAccount = null;

    #[ORM\Column]
    #[Groups(['transaction:read'])]
    private ?float $amount = null;

    #[ORM\Column(length: 3)]
    #[Groups(['transaction:read'])]
    private ?string $currency = null;

    #[ORM\Column]
    #[Groups(['transaction:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['transaction:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['transaction:read'])]
    private ?float $exchangeRate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceAccount(): ?Account
    {
        return $this->sourceAccount;
    }

    public function setSourceAccount(?Account $sourceAccount): static
    {
        $this->sourceAccount = $sourceAccount;

        return $this;
    }

    public function getDestinationAccount(): ?Account
    {
        return $this->destinationAccount;
    }

    public function setDestinationAccount(?Account $destinationAccount): static
    {
        $this->destinationAccount = $destinationAccount;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getExchangeRate(): ?float
    {
        return $this->exchangeRate;
    }

    public function setExchangeRate(float $exchangeRate): static
    {
        $this->exchangeRate = $exchangeRate;

        return $this;
    }
}

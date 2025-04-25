<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['client:read', 'account:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['client:read', 'account:read'])]
    private ?string $accountNumber = null;

    #[ORM\Column(length: 3)]
    #[Groups(['client:read', 'account:read'])]
    private ?string $currency = null;

    #[ORM\Column]
    #[Groups(['client:read', 'account:read'])]
    private ?float $balance = 0.0;

    #[ORM\ManyToOne(inversedBy: 'accounts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['account:read'])]
    private ?Client $client = null;

    #[ORM\OneToMany(mappedBy: 'sourceAccount', targetEntity: Transaction::class)]
    private Collection $outgoingTransactions;

    #[ORM\OneToMany(mappedBy: 'destinationAccount', targetEntity: Transaction::class)]
    private Collection $incomingTransactions;

    public function __construct()
    {
        $this->outgoingTransactions = new ArrayCollection();
        $this->incomingTransactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set ID (for testing purposes only)
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber): static
    {
        $this->accountNumber = $accountNumber;

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

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getOutgoingTransactions(): Collection
    {
        return $this->outgoingTransactions;
    }

    public function addOutgoingTransaction(Transaction $transaction): static
    {
        if (!$this->outgoingTransactions->contains($transaction)) {
            $this->outgoingTransactions->add($transaction);
            $transaction->setSourceAccount($this);
        }

        return $this;
    }

    public function removeOutgoingTransaction(Transaction $transaction): static
    {
        if ($this->outgoingTransactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getSourceAccount() === $this) {
                $transaction->setSourceAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getIncomingTransactions(): Collection
    {
        return $this->incomingTransactions;
    }

    public function addIncomingTransaction(Transaction $transaction): static
    {
        if (!$this->incomingTransactions->contains($transaction)) {
            $this->incomingTransactions->add($transaction);
            $transaction->setDestinationAccount($this);
        }

        return $this;
    }

    public function removeIncomingTransaction(Transaction $transaction): static
    {
        if ($this->incomingTransactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getDestinationAccount() === $this) {
                $transaction->setDestinationAccount(null);
            }
        }

        return $this;
    }

    /**
     * Get all transactions (both incoming and outgoing)
     *
     * @return array<Transaction>
     */
    public function getAllTransactions(): array
    {
        $transactions = array_merge(
            $this->outgoingTransactions->toArray(),
            $this->incomingTransactions->toArray()
        );

        usort($transactions, function (Transaction $a, Transaction $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        return $transactions;
    }
}

<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\CurrencyExchangeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsCommand(
    name: 'app:update-exchange-rates',
    description: 'Update exchange rates from the external API',
)]
class UpdateExchangeRatesCommand extends Command
{
    private const DEFAULT_BASE_CURRENCIES = ['USD', 'EUR', 'GBP'];
    private const DEFAULT_TARGET_CURRENCIES = ['USD', 'EUR', 'GBP'];

    public function __construct(
        private CurrencyExchangeService $currencyExchangeService
    ) {
        parent::__construct();
    }

    /**
     * Create a message handler for this command
     */
    public function asMessageHandler(): callable
    {
        return function () {
            $this->updateExchangeRates();
        };
    }

    protected function configure(): void
    {
        $this
            ->addArgument('base-currencies', InputArgument::OPTIONAL, 'Comma-separated list of base currencies')
            ->addArgument('target-currencies', InputArgument::OPTIONAL, 'Comma-separated list of target currencies');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Parse base currencies
        $baseCurrenciesArg = $input->getArgument('base-currencies');
        $baseCurrencies = $baseCurrenciesArg ? explode(',', $baseCurrenciesArg) : self::DEFAULT_BASE_CURRENCIES;

        // Parse target currencies
        $targetCurrenciesArg = $input->getArgument('target-currencies');
        $targetCurrencies = $targetCurrenciesArg ? explode(',', $targetCurrenciesArg) : self::DEFAULT_TARGET_CURRENCIES;

        $io->title('Updating Exchange Rates');
        $io->section('Base currencies: ' . implode(', ', $baseCurrencies));
        $io->section('Target currencies: ' . implode(', ', $targetCurrencies));

        $result = $this->updateExchangeRates($baseCurrencies, $targetCurrencies, function ($message) use ($io) {
            $io->write($message);
        }, function ($message) use ($io) {
            $io->writeln($message);
        });

        $io->newLine();
        $io->success("Exchange rates update completed: {$result['success']} succeeded, {$result['error']} failed.");

        return $result['error'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Update exchange rates
     *
     * @param array $baseCurrencies Base currencies to update
     * @param array $targetCurrencies Target currencies to update
     * @param callable|null $writeCallback Callback for writing messages
     * @param callable|null $writelnCallback Callback for writing messages with newline
     * @return array Result with success and error counts
     */
    private function updateExchangeRates(
        array $baseCurrencies = null,
        array $targetCurrencies = null,
        callable $writeCallback = null,
        callable $writelnCallback = null
    ): array {
        $baseCurrencies = $baseCurrencies ?? self::DEFAULT_BASE_CURRENCIES;
        $targetCurrencies = $targetCurrencies ?? self::DEFAULT_TARGET_CURRENCIES;

        $successCount = 0;
        $errorCount = 0;

        foreach ($baseCurrencies as $baseCurrency) {
            if ($writeCallback) {
                $writeCallback("Updating rates for {$baseCurrency}... ");
            }

            try {
                $rates = $this->currencyExchangeService->fetchAndStoreAllRates($baseCurrency, $targetCurrencies);

                if ($writelnCallback) {
                    $writelnCallback("<info>Success</info> (" . count($rates) . " rates updated)");
                }

                $successCount++;
            } catch (\Exception $e) {
                if ($writelnCallback) {
                    $writelnCallback("<error>Error</error> (" . $e->getMessage() . ")");
                }

                $errorCount++;
            }
        }

        return [
            'success' => $successCount,
            'error' => $errorCount
        ];
    }
}

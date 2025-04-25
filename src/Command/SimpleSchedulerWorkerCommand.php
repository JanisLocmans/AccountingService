<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\UpdateExchangeRatesMessage;
use App\MessageHandler\UpdateExchangeRatesMessageHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:simple-scheduler-worker',
    description: 'Run a simple scheduler worker to update exchange rates',
)]
class SimpleSchedulerWorkerCommand extends Command
{
    public function __construct(
        private UpdateExchangeRatesMessageHandler $messageHandler
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Simple Scheduler Worker');

        $io->section('Starting scheduler worker...');
        $io->writeln('Press Ctrl+C to stop the worker');

        $io->section('Waiting for scheduled tasks...');

        $lastRunTime = 0;

        while (true) {
            $now = time();

            // Run every minute (60 seconds)
            if ($now - $lastRunTime >= 60) {
                $io->writeln(sprintf(
                    '[%s] Running exchange rate update',
                    (new \DateTimeImmutable())->format('Y-m-d H:i:s')
                ));

                // Create and handle the message
                $message = new UpdateExchangeRatesMessage();
                $this->messageHandler->__invoke($message);

                $lastRunTime = $now;
            }

            sleep(10); // Check every 10 seconds
        }

        return Command::SUCCESS;
    }
}

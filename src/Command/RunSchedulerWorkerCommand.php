<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Messenger\SchedulerTransportFactory;
use Symfony\Component\Scheduler\Schedule;

#[AsCommand(
    name: 'app:run-scheduler-worker',
    description: 'Run the scheduler worker to process scheduled tasks',
)]
class RunSchedulerWorkerCommand extends Command
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ContainerInterface $container,
        private Schedule $schedule,
        private EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Scheduler Worker');

        $io->section('Starting scheduler worker...');
        $io->writeln('Press Ctrl+C to stop the worker');

        // Get all recurring messages from the container
        $recurringMessages = $this->getRecurringMessages();
        $io->writeln(sprintf('Loaded schedule with %d messages', count($recurringMessages)));

        foreach ($recurringMessages as $message) {
            $io->writeln(sprintf('  - Message: %s', get_class($message->getMessage())));
            $io->writeln(sprintf('    Next run: %s', $message->getTrigger()->getNextRunDate()->format('Y-m-d H:i:s')));
        }

        $io->section('Waiting for scheduled tasks...');

        while (true) {
            foreach ($recurringMessages as $message) {
                if ($message->getTrigger()->shouldRun()) {
                    $io->writeln(sprintf(
                        '[%s] Running scheduled task: %s',
                        (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                        get_class($message->getMessage())
                    ));

                    $this->messageBus->dispatch($message->getMessage());
                }
            }

            sleep(10); // Check every 10 seconds
        }

        return Command::SUCCESS;
    }

    /**
     * Get all recurring messages from the container
     *
     * @return array<\Symfony\Component\Scheduler\RecurringMessage>
     */
    private function getRecurringMessages(): array
    {
        // For simplicity, we'll just return our update exchange rates message
        return [
            $this->container->get('app.scheduler.update_exchange_rates.message')
        ];
    }
}

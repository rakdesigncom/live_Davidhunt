<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Console\Command;

use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\ProviderConfigChecker;
use Magento\AdobeIoEventsClient\Console\CreateEventProvider;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Command for unsubscribing from events
 */
class EventUnsubscribeCommand extends Command
{
    private const ARGUMENT_EVENT_CODE = 'event-code';

    /**
     * @var EventSubscriberInterface
     */
    private EventSubscriberInterface $eventSubscriber;

    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var ProviderConfigChecker
     */
    private ProviderConfigChecker $providerConfigChecker;

    /**
     * @param EventSubscriberInterface $eventSubscriber
     * @param EventList $eventList
     * @param ProviderConfigChecker $providerConfigChecker
     */
    public function __construct(
        EventSubscriberInterface $eventSubscriber,
        EventList $eventList,
        ProviderConfigChecker $providerConfigChecker
    ) {
        $this->eventSubscriber = $eventSubscriber;
        $this->eventList = $eventList;
        $this->providerConfigChecker = $providerConfigChecker;
        parent::__construct('events:unsubscribe');
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Removes the subscription to the supplied event')
            ->addArgument(
                self::ARGUMENT_EVENT_CODE,
                InputArgument::REQUIRED,
                'Event code to unsubscribe from'
            );

        parent::configure();
    }

    /**
     * Removes the subscription to the event.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $eventCode = strtolower($input->getArgument(self::ARGUMENT_EVENT_CODE));
        try {
            $event = $this->eventList->get($eventCode);
            if ($event === null) {
                $output->writeln(sprintf(
                    "<error>The '%s' event is not registered. You cannot unsubscribe from it</error>",
                    $eventCode
                ));
                return Cli::RETURN_FAILURE;
            }

            if (!$this->providerConfigChecker->check($event->getProviderId())) {
                $output->writeln(
                    sprintf(
                        "<error>No event provider is configured, please run bin/magento %s</error>",
                        CreateEventProvider::COMMAND_NAME
                    )
                );
                return Cli::RETURN_FAILURE;
            }

            $this->eventSubscriber->unsubscribe($event);
            $output->writeln(sprintf("Successfully unsubscribed from the '%s' event", $eventCode));
        } catch (Throwable $e) {
            $output->writeln("<error>Error unsubscribing from event '$eventCode': {$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}

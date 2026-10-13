<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Console\Command;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\Processor\EventDataProcessor;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleInterface;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Command for displaying a list of subscribed events
 */
class EventListCommand extends Command
{
    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @param EventList $eventList
     * @param string|null $name
     */
    public function __construct(
        EventList $eventList,
        ?string $name = null
    ) {
        $this->eventList = $eventList;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('events:list')
            ->setDescription('Shows list of subscribed events');

        parent::configure();
    }

    /**
     * Displays a list of subscribed events.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $events = $this->eventList->getAll();

            ksort($events);

            if ($output->isVerbose()) {
                $table = $this->getTable($events, $output);
                $table->render();
            } else {
                foreach ($events as $event) {
                    if ($event->isEnabled()) {
                        $name = $event->getName();
                        if (!empty($event->getParent())) {
                            $name .= sprintf(' [parent: %s]', $event->getParent());
                        }
                        $output->writeln($name);
                    }
                }
            }

            return Cli::RETURN_SUCCESS;
        } catch (Throwable $e) {
            $output->writeln($e->getMessage());
            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * Returns a table object for the provided list of events
     *
     * @param Event[] $events
     * @param OutputInterface $output
     * @return Table
     */
    private function getTable(array $events, OutputInterface $output): Table
    {
        $table = new Table($output);

        foreach ($events as $event) {
            if ($event->isEnabled()) {
                $headers = [
                    Event::EVENT_NAME,
                    Event::EVENT_PARENT,
                    Event::EVENT_FIELDS,
                    Event::EVENT_RULES,
                    Event::EVENT_PROCESSORS,
                    Event::EVENT_PRIORITY
                ];
                $rows = [
                    Event::EVENT_NAME => $event->getName(),
                    Event::EVENT_PARENT => $event->getParent() ?? '',
                    Event::EVENT_FIELDS => $this->formatFields($event->getEventFields()),
                    Event::EVENT_RULES => $this->formatRules($event->getRules()),
                    Event::EVENT_PROCESSORS => $this->formatProcessors($event->getProcessors()),
                    Event::EVENT_PRIORITY => $event->isPriority() ? 'true' : 'false'
                ];
                if ($output->isVeryVerbose()) {
                    $headers[] = 'HIPAA audit required';
                    $headers[] = Event::EVENT_DESTINATION;
                    $headers[] = 'Event provider id';
                    $rows[Event::EVENT_HIPAA_AUDIT_REQUIRED] = $event->isHipaaAuditRequired() ? 'true' : 'false';
                    $rows[Event::EVENT_DESTINATION] = $event->getDestination();
                    $rows[Event::EVENT_PROVIDER_ID] = $event->getProviderId() ?? '';
                }

                $table->setHeaders($headers);
                $table->addRow($rows);
            }
        }

        return $table;
    }

    /**
     * Converts an array of event rules to a string to be output within a Table.
     *
     * @param array $rules
     * @return string
     */
    private function formatRules(array $rules): string
    {
        $ruleOutput = [];
        foreach ($rules as $rule) {
            $ruleOutput[] = sprintf(
                '{ field: %s, operator: %s, value: %s }',
                $rule[RuleInterface::RULE_FIELD] ?? '',
                $rule[RuleInterface::RULE_OPERATOR] ?? '',
                $rule[RuleInterface::RULE_VALUE] ?? ''
            );
        }
        return str_replace(
            ['"{', '}"'],
            ['{', '}'],
            json_encode($ruleOutput, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Converts an array of event processors to a string to be output within a Table.
     *
     * @param array $processors
     * @return string
     */
    private function formatProcessors(array $processors): string
    {
        $processorOutput = [];
        foreach ($processors as $processor) {
            if (empty($processor[EventDataProcessor::PROCESSOR_PRIORITY])) {
                $processor[EventDataProcessor::PROCESSOR_PRIORITY] = 0;
            }

            $processorOutput[] = sprintf(
                '{ class: %s, priority: %s }',
                $processor[EventDataProcessor::PROCESSOR_CLASS] ?? '',
                $processor[EventDataProcessor::PROCESSOR_PRIORITY]
            );
        }

        return str_replace(
            ['"{', '}"','\\\\'],
            ['{', '}','\\'],
            json_encode($processorOutput, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Converts an array of event field objects to a string to be output within a Table.
     *
     * @param array $eventsFields
     * @return string
     */
    private function formatFields(array $eventsFields): string
    {
        $fieldsOutput = [];
        foreach ($eventsFields as $eventField) {
            if (!empty($eventField->getConverter())) {
                $fieldsOutput[] = sprintf(
                    '{ name: "%s", converter: %s }',
                    $eventField->getName(),
                    $eventField->getConverter()
                );
            } else {
                $fieldsOutput[] = $eventField->getName();
            }
        }

        return str_replace(
            ['"{','\\"', '}"','\\\\'],
            ['{','"', '}','\\'],
            json_encode($fieldsOutput, JSON_PRETTY_PRINT)
        );
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Console\Command;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventFactory;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\ProviderConfigChecker;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeIoEventsClient\Console\CreateEventProvider;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Command for subscribing to events
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EventSubscribeCommand extends Command
{
    private const ARGUMENT_EVENT_CODE = 'event-code';
    private const OPTION_FIELDS = Event::EVENT_FIELDS;
    private const OPTION_FORCE = 'force';
    private const OPTION_PARENT = Event::EVENT_PARENT;
    private const OPTION_RULES = Event::EVENT_RULES;
    private const OPTION_PRIORITY = Event::EVENT_PRIORITY;
    private const OPTION_HIPAA_AUDIT_REQUIRED = Event::EVENT_HIPAA_AUDIT_REQUIRED;
    private const OPTION_DESTINATION = Event::EVENT_DESTINATION;
    private const OPTION_EVENT_PROVIDER_ID = Event::EVENT_PROVIDER_ID;
    private const RULE_FORMAT = 'field|operator|value';
    private const CONVERTER_FORMAT = '--fields=\'{"name":"fieldName", "converter":"converterClassPath"}\'';

    /**
     * @var EventSubscriberInterface
     */
    private EventSubscriberInterface $eventSubscriber;

    /**
     * @var EventFactory
     */
    private EventFactory $eventFactory;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var ProviderConfigChecker
     */
    private ProviderConfigChecker $providerConfigChecker;

    /**
     * @param EventSubscriberInterface $eventSubscriber
     * @param EventFactory $eventFactory
     * @param Json $json
     * @param ProviderConfigChecker $providerConfigChecker
     * @param string|null $name
     */
    public function __construct(
        EventSubscriberInterface $eventSubscriber,
        EventFactory $eventFactory,
        Json $json,
        ProviderConfigChecker $providerConfigChecker,
        ?string $name = null
    ) {
        $this->eventSubscriber = $eventSubscriber;
        $this->eventFactory = $eventFactory;
        $this->json = $json;
        $this->providerConfigChecker = $providerConfigChecker;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('events:subscribe')
            ->setDescription('Subscribes to the event')
            ->addArgument(
                self::ARGUMENT_EVENT_CODE,
                InputArgument::REQUIRED,
                'Event code'
            )
            ->addOption(
                self::OPTION_FORCE,
                'f',
                InputOption::VALUE_NONE,
                'Forces the specified event to be subscribed, even if it hasn\'t been defined locally.'
            )
            ->addOption(
                self::OPTION_FIELDS,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'The list of fields in the event data payload.'
            )
            ->addOption(
                self::OPTION_PARENT,
                null,
                InputOption::VALUE_REQUIRED,
                'The parent event code for an event subscription with rules or as an alias.'
            )
            ->addOption(
                self::OPTION_RULES,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                sprintf(
                    'The list of rules for the event subscription, where each rule is formatted as "%s". ' .
                    'To use this option, you must also specify the "parent" option.',
                    self::RULE_FORMAT
                )
            )
            ->addOption(
                self::OPTION_PRIORITY,
                'p',
                InputOption::VALUE_NONE,
                'Expedites the transmission of this event. ' .
                'Specify this option for events that need to be delivered immediately. ' .
                'By default, events are sent by cron once per minute.'
            )
            ->addOption(
                self::OPTION_DESTINATION,
                'd',
                InputOption::VALUE_REQUIRED,
                'The destination of this event. Specify this option for the events that should be delivered ' .
                'to the custom destination.',
                Event::DESTINATION_DEFAULT
            )
            ->addOption(
                self::OPTION_EVENT_PROVIDER_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'The event provider to which events will be delivered'
            )
            ->addOption(
                self::OPTION_HIPAA_AUDIT_REQUIRED,
                null,
                InputOption::VALUE_NONE,
                'Indicates the event contains data that is subject to HIPAA auditing.',
            );

        parent::configure();
    }

    /**
     * Subscribes to the event.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $providerId = $input->getOption(self::OPTION_EVENT_PROVIDER_ID);
            if (!$this->providerConfigChecker->check($providerId)) {
                $output->writeln(
                    sprintf(
                        "<error>No event provider is configured, please run bin/magento %s</error>",
                        CreateEventProvider::COMMAND_NAME
                    )
                );
                return Cli::RETURN_FAILURE;
            }

            $fields = $input->getOption(self::OPTION_FIELDS);
            $isForced = $input->getOption(self::OPTION_FORCE);
            $parent = $input->getOption(self::OPTION_PARENT);
            $rules = $input->getOption(self::OPTION_RULES);

            if (empty($fields)) {
                $output->writeln('<error>You must specify at least one field.</error>');
                return Cli::RETURN_FAILURE;
            }

            if (!empty($rules) && empty($parent)) {
                $output->writeln('<error>The "rules" option must be used with the "parent" option.</error>');
                return Cli::RETURN_FAILURE;
            }

            $event = $this->eventFactory->create([
                Event::EVENT_NAME => $input->getArgument(self::ARGUMENT_EVENT_CODE),
                Event::EVENT_FIELDS => $this->formatFields($fields),
                Event::EVENT_PARENT => $parent,
                Event::EVENT_RULES => $this->convertRules($rules),
                Event::EVENT_PRIORITY => $input->getOption(self::OPTION_PRIORITY),
                Event::EVENT_HIPAA_AUDIT_REQUIRED => $input->getOption(self::OPTION_HIPAA_AUDIT_REQUIRED),
                Event::EVENT_DESTINATION => $input->getOption(self::OPTION_DESTINATION),
                Event::EVENT_PROVIDER_ID => $providerId
            ]);

            $this->eventSubscriber->subscribe($event, $isForced);
            $output->writeln(sprintf('The subscription %s was successfully created', $event->getName()));

            if ($isForced) {
                $output->writeln(
                    'You must generate or regenerate the AdobeCommerceEvents module and compile after ' .
                    'forcing a subscription. Run the following commands:' . PHP_EOL .
                    'bin/magento events:generate:module' . PHP_EOL . 'bin/magento setup:di:compile'
                );
            }
        } catch (Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }

        return CLI::RETURN_SUCCESS;
    }

    /**
     * Converts an array of strings represented rules to an array with the rule structure expected for Event objects.
     *
     * Example:
     *      ['field_id|operator|value'] => [[field=>'field_id', operator=>'operator', value=>'value']]
     *
     * @param array $rules
     * @return array
     * @throws LocalizedException
     */
    private function convertRules(array $rules): array
    {
        $convertedRules = [];

        foreach ($rules as $rule) {
            $ruleComponents = explode('|', trim($rule, '\'\"'), 3);
            if (count($ruleComponents) != 3) {
                throw new LocalizedException(
                    __(sprintf(
                        'Input rules must be formatted as "%s"',
                        self::RULE_FORMAT
                    ))
                );
            }

            $convertedRules[] = array_combine(
                [RuleInterface::RULE_FIELD, RuleInterface::RULE_OPERATOR, RuleInterface::RULE_VALUE],
                $ruleComponents
            );
        }

        return $convertedRules;
    }

    /**
     * Formats fields and converters entered using CLI from string to array.
     *
     * Example:
     * ["status","{"name":"sku", "converter":"converterClass"}"] =>
     * [["name" => "status"],["name" => "sku","converter" => "converterClass"]]
     *
     * @param array $fields
     * @return array
     * @throws ValidatorException
     */
    private function formatFields(array $fields): array
    {
        $formattedFields = [];
        foreach ($fields as $field) {
            $field = trim($field);
            if (preg_match('/^[\w\_\-\.\[\]\*]+$/', $field)) {
                $formattedFields[] = ['name' => $field];
                continue;
            }

            $field = str_replace('\\', '\\\\', $field);
            try {
                $decodedField = $this->json->unserialize($field);
            } catch (\InvalidArgumentException $e) {
                throw new ValidatorException(
                    __(sprintf(
                        'Field converters must be formatted as "%s"',
                        self::CONVERTER_FORMAT
                    ))
                );
            }

            if ($decodedField === null) {
                throw new ValidatorException(
                    __(sprintf(
                        'Field converters must be formatted as "%s"',
                        self::CONVERTER_FORMAT
                    ))
                );
            }

            $formattedFields[] = $decodedField;
        }

        return $formattedFields;
    }
}

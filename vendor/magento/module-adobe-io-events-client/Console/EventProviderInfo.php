<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Console;

use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\EventProviderClient;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to get details for configured event provider
 */
class EventProviderInfo extends Command
{
    public const COMMAND_NAME = 'events:provider:info';

    public const OPTION_PROVIDER_ID = 'provider-id';

    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param EventProviderClient $eventProviderClient
     * @param EventProviderFactory $eventProviderFactory
     */
    public function __construct(
        private AdobeIOConfigurationProvider $configurationProvider,
        private EventProviderClient $eventProviderClient,
        private EventProviderFactory $eventProviderFactory
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription(
            "Returns details about an event provider"
        );
        $this->addOption(
            self::OPTION_PROVIDER_ID,
            null,
            InputOption::VALUE_OPTIONAL,
            'The ID of an event provider. When this option is not used, information for the event provider ' .
                'set in the system configuration is returned.'
        );

        parent::configure();
    }

    /**
     * @inheritDoc
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $providerId = $input->getOption(self::OPTION_PROVIDER_ID);
        if (!empty($providerId)) {
            $provider = $this->eventProviderFactory->create(['data' => ['id' => $providerId]]);
        } else {
            $provider = $this->configurationProvider->getProvider();

            if ($provider === null) {
                $output->writeln('No configured event provider found');
                return Cli::RETURN_FAILURE;
            }
        }

        try {
            $providerInfo = $this->eventProviderClient->getEventProvider($provider);
        } catch (LocalizedException $exception) {
            $output->writeln(sprintf(
                '<error>%s</error>',
                $exception->getMessage()
            ));

            return Cli::RETURN_FAILURE;
        }

        $output->writeln('<info>Configured event provider details:</info>');
        foreach (["id", "label", "description"] as $attribute) {
            if (isset($providerInfo[$attribute])) {
                $output->writeln(sprintf("- %s: %s", $attribute, $providerInfo[$attribute]));
            }
        }

        return Cli::RETURN_SUCCESS;
    }
}

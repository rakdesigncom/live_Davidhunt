<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2025 Adobe
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

namespace Magento\AdobeCommerceEventsClient\Event\EventInfo;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Filter\EventFieldsFilter;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Converter\ClassToArrayConverterInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Controller\Account\CreatePost;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

/**
 * @inheritDoc
 */
class EventsPredefinedInfo implements PredefinedEventInfoInterface
{
    public const TYPE_CLASS = 'class';
    public const TYPE_SCALAR = 'scalar';

    /**
     * @param ClassToArrayConverterInterface $classToArrayConverter
     * @param EventInfoExtenderInterface $eventInfoExtender
     */
    public function __construct(
        private readonly ClassToArrayConverterInterface $classToArrayConverter,
        private readonly EventInfoExtenderInterface $eventInfoExtender
    ) {
    }

    /**
     * Returns event info based on the configuration, returns null if configuration for provided event does not exist.
     *
     * @param Event $event
     * @return array[]|null
     */
    public function get(Event $event): ?array
    {
        $config = $this->getConfig($event->getName());
        if (empty($config)) {
            return null;
        }

        foreach ($config as $key => $valueConfig) {
            $config[$key] = $this->transformConfig($valueConfig);
        }

        return $config;
    }

    /**
     * Returns configuration for the events payload information.
     *
     * @param string $eventName
     * @return array[]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfig(string $eventName): array
    {
        $config = [
            'observer.checkout_submit_all_after' => [
                'order' => [
                    'type' => self::TYPE_CLASS,
                    'value' => Order::class,
                ],
                'quote' => [
                    'type' => self::TYPE_CLASS,
                    'value' => Quote::class,
                ],
            ],
            'observer.customer_login' => [
                'customer' => [
                    'type' => self::TYPE_CLASS,
                    'value' => CustomerInterface::class,
                ],
            ],
            'observer.customer_register_success' => [
                'customer' => [
                    'type' => self::TYPE_CLASS,
                    'value' => CustomerInterface::class,
                ],
                'account_controller' => [
                    'type' => self::TYPE_CLASS,
                    'value' => CreatePost::class,
                ],
            ],
            'observer.magento_customercustomattributes_attribute_save' => [
                'attribute' => [
                    'id' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'int'
                    ],
                    'attribute_id' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'int'
                    ],
                    'attribute_code' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'frontend_label' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'frontend_input' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'is_required' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'bool'
                    ],
                    'default_value_text' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'default_value_yesno' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'bool'
                    ],
                    'default_value_date' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'date_range_min' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'date_range_max' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'default_value_textarea' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'input_validation' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'file_extensions' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'input_filter' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'is_used_in_grid' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'bool'
                    ],
                    'is_visible_in_grid' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'bool'
                    ],
                    'is_filterable_in_grid' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'bool'
                    ],
                    'is_searchable_in_grid' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'bool'
                    ],
                    'is_used_for_customer_segment' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'bool'
                    ],
                    'is_visible' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'bool'
                    ],
                    'sort_order' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'mixed'
                    ],
                    'used_in_forms' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'array'
                    ],
                    'dropdown_attribute_validation' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'dropdown_attribute_validation_unique' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'reset_is-default_option' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'backend_model' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'source_model' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'backend_type' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'is_user_defined' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'bool'
                    ],
                    'is_system' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'bool'
                    ],
                    'attribute_set_id' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'int'
                    ],
                    'attribute_group_id' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'int'
                    ],
                    'default_value' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'entity_type_id' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string'
                    ],
                    'validate_rules' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'array'
                    ],
                    'store_labels' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'string[]'
                    ],
                    '_isNew' => [
                        'type' => self::TYPE_SCALAR,
                        'value' => 'mixed'
                    ],
                ],
            ],
            'observer.customer_group_save_commit_after' => [
                'customer_group_id' => [
                    'type' => self::TYPE_SCALAR,
                    'value' => 'int'
                ],
                'customer_group_code' => [
                    'type' => self::TYPE_SCALAR,
                    'value' => 'string'
                ],
                'tax_class_id' => [
                    'type' => self::TYPE_SCALAR,
                    'value' => 'int'
                ],
                'extension_attributes' => [
                    'type' => self::TYPE_SCALAR,
                    'value' => 'array'
                ],
                EventFieldsFilter::FIELD_ORIGINAL_DATA => [
                    'type' => self::TYPE_SCALAR,
                    'value' => 'array'
                ],
                EventFieldsFilter::FIELD_IS_NEW => [
                    'type' => self::TYPE_SCALAR,
                    'value' => 'bool'
                ],
            ],
            'observer.customer_group_delete_commit_after' => [
                'customer_group_id' => [
                    'type' => self::TYPE_SCALAR,
                    'value' => 'int'
                ],
                'customer_group_code' => [
                    'type' => self::TYPE_SCALAR,
                    'value' => 'string'
                ],
                'tax_class_id' => [
                    'type' => self::TYPE_SCALAR,
                    'value' => 'int'
                ],
                EventFieldsFilter::FIELD_ORIGINAL_DATA => [
                    'type' => self::TYPE_SCALAR,
                    'value' => 'array'
                ],
                EventFieldsFilter::FIELD_IS_NEW => [
                    'type' => self::TYPE_SCALAR,
                    'value' => 'bool'
                ],
            ],
            'observer.sales_order_place_after' => [
                'order' => [
                    'type' => self::TYPE_CLASS,
                    'value' => Order::class,
                ],
            ],
            'observer.sales_order_place_before' => [
                'order' => [
                    'type' => self::TYPE_CLASS,
                    'value' => Order::class,
                ],
            ],
            'observer.order_cancel_after' => [
                'order' => [
                    'type' => self::TYPE_CLASS,
                    'value' => Order::class,
                ],
            ],
        ];

        return $config[$eventName] ?? [];
    }

    /**
     * Transforms provided configuration to array of properties with their types
     *
     * @param array $valueConfig
     * @return array|string
     */
    private function transformConfig(array $valueConfig): array|string
    {
        if (!isset($valueConfig['type'])) {
            return array_map(function ($configValue) {
                return $this->transformConfig($configValue);
            }, $valueConfig);
        }

        if ($valueConfig['type'] === self::TYPE_CLASS) {
            return $this->eventInfoExtender->extend(
                $valueConfig['value'],
                $this->classToArrayConverter->convert($valueConfig['value'], 2)
            );
        }

        return $valueConfig['value'];
    }
}

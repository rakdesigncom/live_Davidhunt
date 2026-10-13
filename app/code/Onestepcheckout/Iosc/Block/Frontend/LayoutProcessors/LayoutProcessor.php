<?php
/**
 * OneStepCheckout
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to One Step Checkout AS software license.
 *
 * License is available through the world-wide-web at this URL:
 * https://www.onestepcheckout.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to mail@onestepcheckout.com so we can send you a copy immediately.
 *
 * @category   onestepcheckout
 * @package    onestepcheckout_iosc
 * @copyright  Copyright (c) 2017 OneStepCheckout  (https://www.onestepcheckout.com/)
 * @license    https://www.onestepcheckout.com/LICENSE.txt
 */
namespace Onestepcheckout\Iosc\Block\Frontend\LayoutProcessors;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\View;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Onestepcheckout\Iosc\Helper\Data;

class LayoutProcessor implements LayoutProcessorInterface
{

    /**
     * @var array
     */
    public const CSS_CLASSES = [
        '0' => [
            'key' => 'iosc-quarter',
            'width' => 25
        ],
        '1' => [
            'key' => 'iosc-half',
            'width' => 50
        ],
        '2' => [
            'key' => 'iosc-third',
            'width' => 75
        ],
        '3' => [
            'key' => 'iosc-whole',
            'width' => 100
        ]
     ];

    /**
     *
     * @var array
     */
     public $paymentMethods = [];
    /**
     * @var View
     */
     protected $viewObject;
    /**
     * @var ScopeConfigInterface
     */
     protected $scopeConfig;
    /**
     * @var Session
     */
     protected $checkoutSession;
    /**
     * @var PaymentMethodManagementInterface
     */
     protected $paymentMethodManagement;
    /**
     * @var Data
     */
     protected $helper;

    /**
     *
     * @param View $view
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param Data $helper
     */
    public function __construct(
        View $view,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        PaymentMethodManagementInterface $paymentMethodManagement,
        Data $helper
    ) {

        $this->viewObject = $view;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     *
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function process($jsLayout)
    {

        if (!$this->helper->isEnabled()) {
            return $jsLayout;
        }
        if ($this->viewObject->getDefaultLayoutHandle() != 'checkout_index_index') {
            return $jsLayout;
        }
        $unset = [
           'general',
           'geoip',
           'cartskip'
        ];
        $special = [
           'billingfields',
           'shippingfields'
        ];
        $config = $this->getConfig('onestepcheckout_iosc', $unset, $special);

        if (empty($config)) {
            return $jsLayout;
        }
        $specialConfig = [];

        foreach ($special as $node) {
            $specialConfig[$node] = $config[$node];
            unset($config[$node]);
        }
        foreach ($specialConfig as $field => $values) {
            $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            if ($field == 'billingfields') {
                $billingFieldTargets = [
                   'billingAddressshared'
                ];

                $fieldsAreShared = $this->scopeConfig
                   ->getValue('checkout/options/display_billing_address_on', $scopeStore);
                if ($fieldsAreShared) {
                    $fields = [
                       'shared-form' => $jsLayout['components']['checkout']
                                       ['children']['steps']
                                       ['children']['billing-step']
                                       ['children']['payment']
                                       ['children']['afterMethods']
                                       ['children']['billing-address-form']
                       ];
                } else {
                    $fields =   $jsLayout['components']['checkout']
                               ['children']['steps']
                               ['children']['billing-step']
                               ['children']['payment']
                               ['children']['payments-list']
                               ['children'];
                    if (!empty(current($this->getPaymentMethods()))) {
                        $billingFieldTargets[] = 'billingAddress'.current($this->getPaymentMethods());
                    }
                }
                $values['cnf']['open'] = $this->checkoutSession->getOscBaOpenState();
                $jsLayout['components']['checkout']
                       ['children']['steps']
                       ['children']['shipping-step']
                       ['children']['iosc-billing-fields']['cnf'] = $values['cnf'];

                $lastfield = false;

                if ($fields) {
                    foreach ($fields as $k => $v) {
                        if (! empty($v['children']['form-fields']) && isset($v['dataScopePrefix'])) {
                            if (in_array($v['dataScopePrefix'], $billingFieldTargets)) {
                                $separateStreetFields = $this->scopeConfig
                                   ->getValue('onestepcheckout_iosc/billingfields/separatestreetfields', $scopeStore);

                                if ($separateStreetFields) {
                                    $groupName = 'street';

                                    if (!empty($v['children']['form-fields']['children'][$groupName])) {
                                        $lines = $this->explodeFieldLines(
                                            $v['children']['form-fields']['children'][$groupName],
                                            $groupName
                                        );

                                        unset($v['children']['form-fields']['children'][$groupName]);
                                        // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                                        $v['children']['form-fields']['children'] = array_merge(
                                            $v['children']['form-fields']['children'],
                                            $lines
                                        );

                                    }
                                }
                                $fields[$k]['children']['form-fields']['children'] =
                                $this->manageAddressFields(
                                    $v['children']['form-fields']['children'],
                                    $values['cnf'],
                                    'billingfields'
                                );
                                if (!$lastfield) {

                                    $lastfield = $this
                                    ->getLastField($fields[$k]['children']['form-fields']['children']);

                                    $jsLayout['components']['checkout']
                                       ['children']['steps']
                                       ['children']['shipping-step']
                                       ['children']['iosc-billing-fields']['lastVisibleField'] = $lastfield;
                                }
                            } else {
                                unset($v['children']['form-fields']);
                            }
                        }
                    }
                    if ($fieldsAreShared) {
                        $jsLayout['components']['checkout']
                               ['children']['steps']
                               ['children']['billing-step']
                               ['children']['payment']
                               ['children']['afterMethods']
                               ['children']['billing-address-form'] = $fields['shared-form'];
                    } else {
                        $jsLayout['components']['checkout']
                               ['children']['steps']
                               ['children']['billing-step']
                               ['children']['payment']
                               ['children']['payments-list']
                               ['children'] = $fields;
                    }
                }
            }

            if ($field == 'shippingfields') {
                $fields = $jsLayout['components']['checkout']
                               ['children']['steps']
                               ['children']['shipping-step']
                               ['children']['shippingAddress']
                               ['children']['shipping-address-fieldset']
                               ['children'];
                $config[$field] = $values;
                $separateStreetFields = $this->scopeConfig
                ->getValue('onestepcheckout_iosc/shippingfields/separatestreetfields', $scopeStore);

                if ($separateStreetFields) {
                    $groupName = 'street';

                    if (!empty($fields[$groupName]['children'])) {
                        $lines = $this->explodeFieldLines($fields[$groupName], $groupName);
                        unset($fields[$groupName]);
                        // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                        $fields = array_merge($fields, $lines);
                    }
                }
                $fields = $this->manageAddressFields($fields, $values['cnf'], 'shippingfields');

                $config[$field]['lastVisibleField'] = $this->getLastField($fields);
                $jsLayout['components']['checkout']
                       ['children']['steps']
                       ['children']['shipping-step']
                       ['children']['shippingAddress']
                       ['children']['shipping-address-fieldset']
                       ['children'] = $fields;
            }
        }

        $jsLayout['components']['checkout']
               ['children']['iosc']
               ['children'] = $config;

        return $jsLayout;
    }

    /**
     * Explode children out of parent group and preserve some attributes from parent
     *
     * @param array $group
     * @param string $name
     * @return array
     */
    public function explodeFieldLines(array $group, $name): array
    {
        $lines = [];

        foreach ($group['children'] as $rowNum => $streetRow) {
            $rowName = $name. '.' . $rowNum;
            $lines[$rowName] = $streetRow;
            if ($rowNum == 0) {
                $lines[$rowName]['label'] = __($group['label']);
            } else {
                $lines[$rowName]['label'] = __($group['label'] . ' ' . $rowNum);
            }
            if (isset($lines[$rowName]['dataScope'])) {
                $lines[$rowName]['dataScope'] = $group['dataScope'] . '.' . $rowNum;
            }
        }

        return $lines;
    }

    /**
     * Returns last rendered element dataScope
     *
     * @param array $fields
     * @return string
     */
    public function getLastField($fields): string
    {
        $tmp = [];
        foreach ($fields as $k => $field) {
            if (empty($field['component'])) {
                continue;
            }
            if (!empty($field['children'])) {
                $valueIsSet = false;
                foreach ($field['children'] as $kk => $child) {
                    if (!empty($child['config']['sortOrder'])) {
                        $tmp[$child['config']['sortOrder']] = $k.'.'.$kk;
                        $valueIsSet = true;
                    } elseif (!empty($child['sortOrder'])) {
                        $tmp[$child['sortOrder']] = $k.'.'.$kk;
                        $valueIsSet = true;
                    }
                }
                if (!$valueIsSet && !empty($field['sortOrder'])) {
                    $tmp[$field['sortOrder']] = $k;
                }
            } else {
                if (!empty($field['config']['sortOrder'])) {
                    $tmp[$field['config']['sortOrder']] = $k;
                } elseif (!empty($field['sortOrder'])) {
                    $tmp[$field['sortOrder']] = $k;
                }
            }
        }

        ksort($tmp);
        $lastIndex = end($tmp);
        if (!empty($fields[$lastIndex]['children'])) {
            $lastChild = end($fields[$lastIndex]['children']);
            $lastFieldNumber = (!empty($lastChild['dataScope'])) ?
               $lastChild['dataScope'] : count($fields[$lastIndex]['children']) - 1;
            $lastField = $fields[$lastIndex]['dataScope'].'.'.$lastFieldNumber;
        } else {
            $lastField = $fields[$lastIndex];
        }
        if (isset($lastField['dataScope'])) {
            $lastField = $lastField['dataScope'];
        }

        return $lastField;
    }

    /**
     * Method that manipulates config structure
     *
     * @param array $config
     * @return array
     */
    private function prepFieldConfig($config): array
    {

        $fieldClasses = $this->getFieldClasses();

        foreach ($config as $key => $item) {
            if (isset($item['length'])) {
                $fieldClass = $fieldClasses[$item['length']];
                $config[$key]['fieldWidth'] = $fieldClass['width'];
                $config[$key]['css_class'] = (!empty($item['css_class'])) ?
                       $fieldClass['key'] . ' ' . $item['css_class'] : $fieldClass['key'];
            }
            if (strstr($key, '.')) {
                $parts = explode('.', $key);
                $parent = current($parts);
                $slave = end($parts);

                if (!isset($config[$parent])) {
                    $config[$parent] = $config[$key];
                }

                if (isset($config[$parent])) {
                    $config[$parent]['children'][$slave] = $config[$key];
                }
            }
            if ($key == 'region') {
                $config['region_id'] = $config[$key];
                $config['region_id']['field_id'] = 'region_id';
            }
        }

        return $config;
    }

    /**
     * Compare default values against address object stored data
     *
     * @param array $config
     * @param string $type
     * @param $quote
     * @param $field
     * @return array
     */
    private function getDefaultFieldValue(array $config = [], $type = '', $quote = null, $field = null): array
    {

        $addressObj = null;

        if ($type == 'shippingfields') {
            $addressObj = $quote->getShippingAddress();
        } else {
            $addressObj = $quote->getBillingAddress();
        }
        if (is_object($addressObj)) {
            $newValue = $addressObj->getData($config['field_id']);

            if ($newValue && $newValue !='-') {
                $config['default_value'] = $newValue;
            }
            if (isset($config['children'])) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
            }
        }

        if (!empty($config['css_class'])) {
            $existingClass = [];

            if (isset($field['config']['additionalClasses']) && $field['config']['additionalClasses'] !=1) {
                $existingClass[] = $field['config']['additionalClasses'];
            }

            if (isset($field['additionalClasses']) && $field['additionalClasses'] !=1) {
                $existingClass[] = $field['additionalClasses'];
            }

            if (!empty($existingClass)) {
                $config['css_class'] = (!empty($config['css_class'])) ?
                   $config['css_class'] . ' ' . implode(' ', $existingClass) : implode(' ', $existingClass);
            }
        }

        return $config;
    }

    /**
     * Apply configuration
     *
     * @param array $paramNames
     * @param array $field
     * @param array $fieldConfig
     * @return array
     */
    public function applyConfig(array $paramNames, array $field, array $fieldConfig): array
    {

        if (!empty($fieldConfig)) {
            foreach ($paramNames as $param) {
                $field = $this->applyParam($param, $field, $fieldConfig);
            }
        }

        return $field;
    }

    /**
     * Apply Params
     *
     * @param string $param
     * @param array $field
     * @param array $fieldConfig
     * @return array
     */
    public function applyParam(string $param, array $field, array $fieldConfig): array
    {
        $addRules = $this->scopeConfig->getValue(
            'onestepcheckout_iosc/registration/matchvalidationfrontend',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ?? false;

        $addRulesToFields = ['firstname', 'lastname', 'middlename'];
        if ($addRules && isset($field['cnf']) && in_array($field['cnf']['field_id'], $addRulesToFields)) {
            $field = $this->addValidationRule($field, 'validate-customer-name', '1');
        }
        $assignToParent = false;
        $removeIfNotNeeded = false;
        switch ($param) {
            case 'enabled':
                $map = 'visible';
                break;
            case 'default_value':
                $map = 'value';
                break;
            case 'required':
                $map = ['validation' => 'required-entry'];
                if (empty($fieldConfig['required'])) {
                    $removeIfNotNeeded = 'validation';
                }
                break;
            case 'field_sort':
                $map = 'sortOrder';
                $assignToParent = true;
                break;

            case 'css_class':
                $map = ['config' => 'additionalClasses'];
                break;
            default:
                $map = 'cnf';
                break;
        }

        $field = $this->mapParam($map, $param, $field, $fieldConfig, $assignToParent, $removeIfNotNeeded);

        return $field;
    }

    /**
     * Add Validation Rules
     *
     * @param array $field
     * @param string $ruleName
     * @param string $ruleValue
     * @return array
     */
    private function addValidationRule(
        array $field,
        string $ruleName = 'customer-name',
        string $ruleValue = '1'
    ): array {
        if (isset($field['validation']) && !isset($field['validation'][$ruleName])) {
            $field['validation'][$ruleName] = $ruleValue;
        }
         return $field;
    }

    /**
     * Map Params
     *
     * @param mixed $map
     * @param string $param
     * @param array $field
     * @param array $fieldConfig
     * @param string $assignToParent
     * @param string $removeIfNotNeeded
     * @return array
     */
    public function mapParam(
        $map,
        string $param,
        array $field,
        array $fieldConfig,
        $assignToParent = false,
        $removeIfNotNeeded = false
    ): array {
        $mapIsarray = false;
        if (is_array($map)) {
            $mapIsarray = true;
            foreach ($map as $kk => $vv) {
                $field[$kk][$vv] = (isset($fieldConfig[$param])) ? $fieldConfig[$param] : $fieldConfig;
            }
        } else {
            $field[$map] = (isset($fieldConfig[$param])) ? $fieldConfig[$param] : $fieldConfig;
        }

        if ($removeIfNotNeeded) {
            if ($mapIsarray && isset($field[$removeIfNotNeeded][current($map)])) {
                unset($field[$removeIfNotNeeded][current($map)]);
            } else {
                unset($field[$removeIfNotNeeded]);
            }
        }

        return $field;
    }

    /**
     * Manage Address Fields
     *
     * @param array $fields
     * @param array $cnf
     * @param string $type
     * @return array
     */
    public function manageAddressFields(
        array $fields = [],
        array $cnf = [],
        $type = 'shippingfields'
    ): array {

        $params = ['cnf','enabled', 'default_value', 'required', 'field_sort','css_class'];

        /*
        * iterate over fields  =
        *  getDefaultFieldValue ->
        *      -> applyconfig
        *          -> applyparam
        *              -> mapparam
        */

        foreach ($fields as $fieldName => $field) {
            $isEnabled = true;

            $fieldConfig = (!empty($cnf[$fieldName])) ? $cnf[$fieldName] : [] ;

            if (!isset($fieldConfig['field_id'])) {
                continue;
            }

            $fieldConfig = $this->getDefaultFieldValue($fieldConfig, $type, $this->checkoutSession->getQuote(), $field);
            if ($fieldName == 'region_id') {
                $field['component'] = 'Onestepcheckout_Iosc/js/mixin/region';
                unset($fields['region']);
            }

            if ($fieldConfig) {
                $field = $this->applyConfig($params, $field, $fieldConfig);
                if (isset($field['visible']) && !$field['visible']) {
                    $isEnabled = false;
                }
            } else {
                $isEnabled = false;
            }

            if (!$isEnabled) {
                unset($fields[$fieldName]);
            } else {
                $fields[$fieldName] = $field;
            }
        }

        return $fields;
    }

    /**
     * Return match for field width defining classes
     *
     * @return array
     */
    public function getFieldClasses(): array
    {
        return self::CSS_CLASSES;
    }

    /**
     * Get a list of payment method codes
     *
     * @return array
     */
    public function getPaymentMethods(): array
    {

        if (!empty($this->paymentMethods)) {
            return $this->paymentMethods;
        }

        $this->paymentMethods = [];
        $quote = $this->checkoutSession->getQuote();
        foreach ($this->paymentMethodManagement->getList($quote->getId()) as $paymentMethod) {
            $this->paymentMethods[] = $paymentMethod->getCode();
        }
        return $this->paymentMethods;
    }

    /**
     * Get the array of default address and customer attributes
     *
     * @param string $config
     * @param array $unset
     * @param array $special
     * @return array
     */
    public function getConfig(
        string $config,
        array $unset = [],
        array $special = []
    ): array {

        $config = $this->scopeConfig->getValue($config, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if (empty($config)) {
            return [];
        }

        foreach ($unset as $key) {
            unset($config[$key]);
        }

        foreach ($special as $key) {
            if (! empty($config[$key]) && is_array($config[$key])) {
                foreach ($config[$key] as $k => $v) {
                    if ($key == $k) {
                        $value = $config[$key][$key];
                        if (! empty($value) && is_string($value)) {
                            // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                            $config[$key] = array_merge($config[$key], $this->helper->getSerialized($value));
                            unset($config[$key][$key]);
                            $config[$key] = $this->prepFieldConfig($config[$key]);
                        }
                    } else {
                        $config[$key]['config'][$k] = $v;
                    }
                }
            }
        }

        foreach ($config as $key => $value) {
            $tmp = [];
            if (! empty($value['config'])) {
                $tmp['config'] = $value['config'];
                unset($value['config']);
            }
            $tmp['component'] = 'Onestepcheckout_Iosc/js/' . $key;
            $tmp['cnf'] = $value;
            $config[$key] = $tmp;
        }

        return $config;
    }
}

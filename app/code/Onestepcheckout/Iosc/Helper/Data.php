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
namespace Onestepcheckout\Iosc\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Zend\Serializer\Adapter\PhpSerialize;
use \Zend\Serializer\Serializer;

/**
 * Helper
 */
class Data extends AbstractHelper
{

    public const CONFIG_PATH = 'onestepcheckout_iosc';
    public const FIELD_ID = 'field_id';

    /**
     * @var $scopeConfig
     */
    protected $scopeConfig;

    /**
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }

    /**
     * Get the array of default address and customer attributes
     *
     * @param array $unset
     * @param array $serialized
     * @param string $return
     * @return array
     */
    public function getConfig(array $unset = [], array $serialized = [], $return = '')
    {
        $result = $this->scopeConfig->getValue(self::CONFIG_PATH, ScopeInterface::SCOPE_STORE);

        foreach ($unset as $key) {
            unset($result[$key]);
        }

        foreach ($serialized as $key) {
            if (! empty($result[$key]) && is_array($result[$key])) {
                if (isset($result[$key][$key])) {
                    $value = $result[$key][$key];
                    if (! empty($value) && is_string($value)) {
                        $result[$key] = $this->getSerialized($value);
                    }
                }
            }
        }

        if (! empty($return)) {
            $result = $result[$return];
        }

        return $result;
    }

    /**
     * Check if OneStepCheckout is enabled in store config
     *
     * @return boolean;
     */
    public function isEnabled()
    {
        $enabled = false;

        $enabled = $this->scopeConfig->getValue(
            self::CONFIG_PATH . '/general/enable',
            ScopeInterface::SCOPE_STORE
        );

        return $enabled;
    }

    /**
     * Return unserialized string from serialized config string
     *
     * @param string $serializedValue
     * @return []
     */
    public function getSerialized($serializedValue)
    {
        $return = [];

        $config = $this->unserialize($serializedValue);

        if (is_array($config)) {
            foreach ($config as $v) {
                $return[$v[self::FIELD_ID]] = $v;
            }
        }

        return $return;
    }

    /**
     * Get the right serializer to support m2 < 2.3 where config object format changed to json zend removed
     *
     * @return NULL|object
     */
    public function getSerializer()
    {
        $serializer = null;

        if (class_exists(Json::class)) {
            $serializer = Json::class;
        }
        if ($serializer === null &&
            class_exists(PhpSerialize::class)
        ) {
            $serializer = PhpSerialize::class;
        }

        if ($serializer !== null) {
            $objectManager = ObjectManager::getInstance();
            $serializer = $objectManager->create($serializer);
        }

        return $serializer;
    }

    /**
     * Unserialize
     *
     * @param string $serializedValue
     * @return []
     */
    public function unserialize($serializedValue)
    {

        $failed = false;
        $response = false;
        $serializer = $this->getSerializer();

        if ($serializer !== null) {

            try {
                $response = $serializer->unserialize($serializedValue);
            } catch (Exception $e) {
                $failed = true;
            }

            if ($failed) {
                try {
                    $response = $serializer->unserialize($serializedValue);
                } catch (Exception $e) {
                    $response = false;
                }
            }
        }

        return $response;
    }

    /**
     * Helper for filtering out enabled array members from serialized value
     *
     * @param array $data
     * @return array
     */
    public function replaceConfigValues($data = [])
    {

        if (!empty($data)) {
            foreach ($data as $key => $cnf) {
                if (strpos($key, 'is_') !== false) {
                    $cnfArray = $this->unserialize($cnf);
                    $cnfArray = array_keys(
                        array_filter($cnfArray, function ($v, $k) {
                            if ($v['enabled']) {
                                return true;
                            } else {
                                return false;
                            }
                        }, ARRAY_FILTER_USE_BOTH)
                    );
                    $data[$key] = $cnfArray;
                }
            }
        }

        return $data;
    }
}

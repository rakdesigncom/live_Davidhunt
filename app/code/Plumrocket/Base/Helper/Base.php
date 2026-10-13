<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license/  End-user License Agreement
 */

namespace Plumrocket\Base\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context as HelperContext;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Plumrocket\Base\Api\ExtensionStatusInterface;

/**
 * @since 1.0.0
 * @deprecated since 2.5.0 - we move logic from this class to others
 */
class Base extends AbstractHelper
{

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Initialize helper
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param HelperContext                             $context
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        HelperContext $context
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct($context);
    }

    /**
     * @deprecated since 2.9.0
     * @see \Plumrocket\Base\Model\Extension\Customer\GetTrueCustomerKey::execute
     * @param string $customerKey
     * @return string
     */
    protected function getTrueCustomerKey($customerKey)
    {
        $trueKey = '';

        if ($customerKey === '532416486b540ea2a1e50c4070b671611b44f52718') {
            $data = explode('_', $this->_getModuleName());
            $modName =  $data[1] ?? '';
            $trueKey = $this->scopeConfig->getValue($modName . '/module/data', ScopeInterface::SCOPE_STORE);
        }

        return $trueKey ?: $customerKey;
    }

    /**
     * Receive magento config value
     * @deprecated since 2.3.0
     * @see \Plumrocket\Base\Helper\AbstractConfig::getConfig
     *
     * @param string      $path
     * @param string|int  $store
     * @param string|null $scope
     * @return mixed
     */
    public function getConfig($path, $store = null, $scope = null)
    {
        if ($scope === null) {
            $scope = ScopeInterface::SCOPE_STORE;
        }
        return $this->scopeConfig->getValue($path, $scope, $store);
    }
}

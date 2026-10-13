<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Model\Extension\Customer;

use Magento\Framework\Config\DataInterface;

/**
 * Reads customer key from pr_extensions.xml
 *
 * @since 2.5.0
 */
class GetKey
{
    /**
     * @var \Magento\Framework\Config\DataInterface
     */
    private $extensionsConfig;

    /**
     * @param \Magento\Framework\Config\DataInterface $extensionsConfig
     */
    public function __construct(
        DataInterface $extensionsConfig
    ) {
        $this->extensionsConfig = $extensionsConfig;
    }

    /**
     * Get customer key.
     *
     * @param string $moduleName e.g. SocialLoginFree
     * @return string
     */
    public function execute(string $moduleName): string
    {
        return (string) $this->extensionsConfig->get("$moduleName/customer/key");
    }
}

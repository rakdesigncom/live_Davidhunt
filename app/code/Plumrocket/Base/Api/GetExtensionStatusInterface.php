<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Api;

/**
 * Get status of Plumrocket extension
 *
 * We have more convenient service for checking status
 * @see \Plumrocket\Base\Api\ExtensionStatusInterface
 *
 * @since 2.3.9
 */
interface GetExtensionStatusInterface
{

    /**
     * Retrieve status of Plumrocket module
     *
     * @param string $moduleName e.g. SocialLoginFree or Plumrocket_SocialLoginFree
     * @return int
     */
    public function execute(string $moduleName): int;
}

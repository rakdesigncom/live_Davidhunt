<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2026 Adobe
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

namespace Magento\AdobeIoEventsClient\Model;

use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Combines JSON decode, structural cleaning, and re-encoding for workspace_configuration payloads.
 */
class WorkspaceConfigurationJsonCleaner implements WorkspaceConfigurationJsonCleanerInterface
{
    private const ALLOWED_DETAILS_KEYS = ['credentials', 'runtime'];

    /**
     * @param Json $json
     */
    public function __construct(private readonly Json $json)
    {
    }

    /**
     * @inheritDoc
     */
    public function clean(string $jsonString): string
    {
        try {
            $decoded = $this->json->unserialize($jsonString);
        } catch (InvalidArgumentException) {
            return $jsonString;
        }

        if (!is_array($decoded)) {
            return $jsonString;
        }

        if (isset($decoded['project']['workspace']['details'])) {
            $decoded['project']['workspace']['details'] = array_intersect_key(
                $decoded['project']['workspace']['details'],
                array_flip(self::ALLOWED_DETAILS_KEYS)
            );
        }

        return $this->json->serialize($decoded);
    }
}

<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Config\Validator;

use InvalidArgumentException;
use Magento\AdobeCommerceEventsClient\Config\ValidatorInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Validates the workspace configuration format
 */
class WorkspaceFormatValidator implements ValidatorInterface
{
    /**
     * @param Json $json
     */
    public function __construct(private Json $json)
    {
    }

    /**
     * Validates workspace configuration format.
     *
     * - checks that workspace configuration is a valid json string
     * - checks that workspace configuration has required parameters project.workspace.details.credentials
     *
     * @param mixed $value
     * @return bool
     * @throws ValidatorException
     */
    public function validate(mixed $value): bool
    {
        try {
            $data = $this->json->unserialize($value);
        } catch (InvalidArgumentException $exception) {
            throw new ValidatorException(
                __('Workspace Configuration has the wrong format: ' . $exception->getMessage())
            );
        }

        $requiredProperties = ['project', 'workspace', 'details', 'credentials'];
        foreach ($requiredProperties as $property) {
            if (!is_array($data) || !isset($data[$property])) {
                throw new ValidatorException(
                    __('Workspace Configuration has the wrong format. Missed the required properties.')
                );
            }
            $data = $data[$property];
        }

        return true;
    }
}

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

namespace Magento\Framework\File {
    if (!function_exists(__NAMESPACE__ . '\is_uploaded_file')) {
        /**
         * Mock is_uploaded_file for tests.
         *
         * @param string $name
         * @return boolean
         */
        function is_uploaded_file($name)
        {
            return $name !== 'magento.jpg';
        }
    }
}

namespace {
    if (!function_exists('is_uploaded_file')) {
        /**
         * Mock global is_uploaded_file for tests.
         *
         * @param string $name
         * @return boolean
         */
        function is_uploaded_file($name)
        {
            return $name !== 'magento.jpg';
        }
    }
}

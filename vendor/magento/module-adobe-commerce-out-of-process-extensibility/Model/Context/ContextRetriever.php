<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context;

use Exception;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Util\CaseConverter;
use Throwable;

/**
 * Retrieves a value from the application context.
 */
class ContextRetriever
{
    /**
     * @param ArgumentExtractor $argumentExtractor
     * @param CaseConverter $caseConverter
     * @param ContextPool $contextPool
     */
    public function __construct(
        private readonly ArgumentExtractor $argumentExtractor,
        private readonly CaseConverter $caseConverter,
        private readonly ContextPool $contextPool,
    ) {
    }

    /**
     * Retrieves a value from a supported application context based on the provided source string.
     *
     * Source string is a dot notation path to the desired value, starting with the context reference.
     * For example: "context_customer_session.get_customer.get_email"
     * Or with arguments: "context_scope_config.get_value{value/path:default}".
     *
     * @param string $source
     * @return mixed
     * @throws ContextRetrieverException
     */
    public function getContextValue(string $source)
    {
        $sourceParts = explode('.', $source);
        if (!$this->contextPool->has($sourceParts[0])) {
            throw new ContextRetrieverException(__(sprintf(
                'Context \'%s\' is unsupported. The context field source \'%s\' cannot be accessed.',
                $sourceParts[0],
                $source
            )));
        }

        try {
            $contextEntity = $this->contextPool->get($sourceParts[0]);
        } catch (Exception $e) {
            throw new ContextRetrieverException(__(sprintf(
                'Unable to access context for source \'%s\'. Exception: %s',
                $source,
                $e->getMessage()
            )));
        }

        foreach (array_slice($sourceParts, 1) as $sourcePart) {
            $method = lcfirst($this->caseConverter->snakeCaseToCamelCase(explode("{", $sourcePart)[0]));
            $arguments = $this->argumentExtractor->extract($sourcePart);

            try {
                $contextEntity = $contextEntity->{$method}(...$arguments);
            } catch (Throwable $e) {
                throw new ContextRetrieverException(__(sprintf(
                    'The context field source \'%s\' cannot be accessed.',
                    $source
                )));
            }
        }

        return $contextEntity;
    }
}

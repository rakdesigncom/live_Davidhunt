<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);
namespace AutifyDigital\LloydscardnetPayment\Logger;

use Monolog\Logger;

/**
 * Logger Handler Class
 */
class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * Lcnet File name
     * @var string
     */
    protected $fileName = '/var/log/lloyds_cardnet.log';
}

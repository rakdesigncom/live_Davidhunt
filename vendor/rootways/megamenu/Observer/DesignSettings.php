<?php
/**
 * Mega Menu DesignSettings Observer.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Observer;

use Magento\Framework\Event\ObserverInterface;

class DesignSettings implements ObserverInterface
{
    /**
     * @var \Rootways\Megamenu\Model\Design\Generator
     */
    protected $_messageManager;

    /**
     * @var \Rootways\Megamenu\Model\Design\Generator
     */
    protected $_design;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Rootways\Megamenu\Model\Design\Generator $cssenerator,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_design = $cssenerator;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_design->menuCss($observer->getData("website"), $observer->getData("store"));
    }
}

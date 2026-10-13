<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Plumrocket\Base\Model\IsModuleInMarketplace;

/**
 * @since 1.0.0
 */
class ProAd extends Field
{

    /**
     * @var string
     */
    protected $_template = 'Plumrocket_LayeredNavigationLite::pro_ad.phtml';

    /**
     * @var \Plumrocket\Base\Model\IsModuleInMarketplace
     */
    private $isModuleInMarketplace;

    /**
     * @param \Magento\Backend\Block\Template\Context      $context
     * @param \Plumrocket\Base\Model\IsModuleInMarketplace $isModuleInMarketplace
     * @param array                                        $data
     */
    public function __construct(
        Context $context,
        IsModuleInMarketplace $isModuleInMarketplace,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->isModuleInMarketplace = $isModuleInMarketplace;
    }

    /**
     * Render promo banner
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderValue(AbstractElement $element): string
    {
        return $this->toHtml();
    }

    /**
     * Get extension url.
     *
     * @return string
     */
    public function getProExtensionUrl(): string
    {
        return $this->isModuleInMarketplace->execute('LayeredNavigationLite')
            ? 'https://marketplace.magento.com/plumrocket-module-productfilter.html'
            : 'https://plumrocket.com/magento-layered-navigation';
    }

    /**
     * Disable inheritance checkbox
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return bool
     */
    protected function _isInheritCheckboxRequired(AbstractElement $element): bool
    {
        return false;
    }

    /**
     * Disable scope label rendering
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderScopeLabel(AbstractElement $element): string
    {
        return '';
    }
}

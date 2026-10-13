<?php
/**
 * Mega Menu CustomMenuBlock Block.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Block\Adminhtml\System\Config;

/**
 * CustomMenuBlock
 */
class CustomMenuBlock extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $_blockFactory;

    /**
     * CustomMenuBlock Construct
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Cms\Model\BlockFactory $blockFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Cms\Model\BlockFactory $blockFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_blockFactory = $blockFactory;
    }

    /**
     * @return mixed
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $cmsBlocks = $this->_blockFactory->create()->getCollection();
            $this->addOption('', '-- No Dropdown --');
            foreach ($cmsBlocks as $block) {
                $this->addOption($block->getIdentifier(), $block->getTitle());
            }
        }

        return parent::_toHtml();
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}

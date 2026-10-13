<?php
/**
 * Class Banner
 *
 * PHP version 8.2
 *
 * @category Sparsh
 * @package  Sparsh_Banner
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\Banner\Block\Adminhtml;

/**
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Banner extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */

    protected function _construct()
    {
        $this->_blockGroup = 'Sparsh_Banner';
        $this->_controller = 'adminhtml';
        $this->_headerText = __('Banner');
        $this->_addButtonLabel = __('Add New Banner');
        parent::_construct();
        if (!$this->_authorization->isAllowed('Sparsh_Banner::add_banner')) {
            $this->removeButton('add');
        }
    }
}

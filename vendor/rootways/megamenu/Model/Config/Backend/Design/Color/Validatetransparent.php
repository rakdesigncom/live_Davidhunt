<?php
/**
 * Mega Menu Validatetransparent Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Model\Config\Backend\Design\Color;

class Validatetransparent extends \Magento\Framework\App\Config\Value
{
    public function beforeSave()
    {
        /*
        $v = $this->getValue();
        if ($v == 'rgba(0, 0, 0, 0)') {
            $this->setValue('transparent');
        }
        return $this;
        */
    }
}

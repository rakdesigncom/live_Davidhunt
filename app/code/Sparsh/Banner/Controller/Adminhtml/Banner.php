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
namespace Sparsh\Banner\Controller\Adminhtml;

/**
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Banner extends Actions
{
    /**
     * Form session key
     *
     * @var string
     */
    protected $formSessionKey = 'sparsh_banner_form_data';

    /**
     * Allowed Key
     *
     * @var string
     */
    protected $allowedKey = 'Sparsh_Banner::manage_banners';

    /**
     * Model class name
     *
     * @var string
     */
    protected $modelClass = \Sparsh\Banner\Model\Banner::class;

    /**
     * Active menu key
     *
     * @var string
     */
    protected $activeMenu = 'Sparsh_Banner::banner';

    /**
     * Status field name
     *
     * @var string
     */
    protected $statusField = 'is_active';

    /**
     * Save request params key
     *
     * @var string
     */
    protected $paramsHolder = 'post';
}

<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Api;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @since 1.0.0
 */
interface ImageRadioButtonSourceInterface extends OptionSourceInterface
{
    /**
     * Return array of options with additional configuration to show images
     *
     * Format:
     *  [
     *      [
     *          'value'   => '<value>',               - value to save
     *          'label'   => '<label>',               - label
     *          'preview' => '<previewUrl>',          - url of preview image
     *          'image'   => '<imageUrl>',            - image to display
     *          'image2x' => '<retinaImageUrl>',      - image for high DPA displays
     *          'anim'    => '<animationUrl>',        - plays on preview hover
     *          'anim2x'  => '<retinaAnimationUrl>',  - animation for high DPA displays
     *      ],
     *      ...
     *  ]
     *
     * @return array[]
     */
    public function toOptionArray(): array;
}

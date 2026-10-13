<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Model\System\Config;

use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\Structure\Element\Section;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Plumrocket\Base\Api\ExtensionInformationListInterface;

/**
 * Retrieve current section in system config and allow check if it's one of plumrocket sections
 *
 * @since 2.3.1
 */
class CurrentSection
{
    /**
     * @var \Magento\Config\Model\Config\Structure
     */
    private $configStructure;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Plumrocket\Base\Api\ExtensionInformationListInterface
     */
    private $informationList;

    /**
     * @param \Magento\Config\Model\Config\Structure                 $configStructure
     * @param \Magento\Framework\App\RequestInterface                $request
     * @param \Plumrocket\Base\Api\ExtensionInformationListInterface $informationList
     */
    public function __construct(
        Structure $configStructure,
        RequestInterface $request,
        ExtensionInformationListInterface $informationList
    ) {
        $this->configStructure = $configStructure;
        $this->request = $request;
        $this->informationList = $informationList;
    }

    /**
     * Get section id.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        if ($section = $this->get()) {
            return $section->getId();
        }

        return null;
    }

    /**
     * Get current section.
     *
     * @return \Magento\Config\Model\Config\Structure\Element\Section|null
     */
    public function get(): ?Section
    {
        $current = $this->request->getParam('section', '');

        if (! $current) {
            try {
                $section = $this->configStructure->getFirstSection();
            } catch (LocalizedException $e) {
                $section = null;
            }
        } else {
            $section = $this->configStructure->getElement($current);
            if (! $section instanceof Section) {
                $section = null;
            }
        }

        return $section;
    }

    /**
     * Check if current section is related one of plumrocket extensions.
     *
     * Ad some extensions that customize admin menu can change tab we try check section id first.
     *
     * @return bool
     */
    public function isPlumrocketExtension(): bool
    {
        if ($this->isPlumrocketSectionId($this->request->getParam('section', ''))) {
            return true;
        }
        $section = $this->get();
        return $section && 'plumrocket' === $section->getAttribute('tab');
    }

    /**
     * Check if section is declared in extensions.xml
     *
     * If the module consists of two sections, such as the Advanced Reviews and Reminders extension,
     * it may not function properly.
     *
     * @param string $sectionId
     * @return bool
     */
    private function isPlumrocketSectionId(string $sectionId): bool
    {
        if (! $sectionId) {
            return false;
        }
        foreach ($this->informationList->getList()->getItems() as $extensionInformation) {
            if ($sectionId === $extensionInformation->getConfigSection()) {
                return true;
            }
        }
        return false;
    }
}

<?php
/**
 * Mega Menu Generator Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Model\Design;

class Generator
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layoutManager;
    
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $_io;
    
    /**
     * @var \Rootways\Megamenu\Helper\Data
     */
    protected $_cssfolder;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $driverFile;

    /**
     * Generator Model.
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\LayoutInterface $layoutManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Filesystem\Io\File $io
     * @param \Rootways\Megamenu\Helper\Data $cssfolder
     * @param \Magento\Framework\Filesystem\Driver\File $driverFile
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\LayoutInterface $layoutManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Filesystem\Io\File $io,
        \Rootways\Megamenu\Helper\Data $cssfolder,
        \Magento\Framework\Filesystem\Driver\File $driverFile
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_storeManager = $storeManager;
        $this->_layoutManager = $layoutManager;
        $this->_messageManager = $messageManager;
        $this->_io = $io;
        $this->_cssfolder = $cssfolder;
        $this->driverFile = $driverFile;
    }
    
    public function menuCss($websiteId, $storeId)
    {
        if (!$websiteId && !$storeId) {
            $websites = $this->_storeManager->getWebsites(false, false);
            foreach ($websites as $id => $value) {
                $this->generateWebsiteCss($id);
            }
        } else {
            if ($storeId) {
                $this->generateStoreCss($storeId);
            } else {
                $this->generateWebsiteCss($websiteId);
            }
        }
    }
    
    protected function generateWebsiteCss($websiteId)
    {
        $website = $this->_storeManager->getWebsite($websiteId);
        foreach ($website->getStoreIds() as $storeId) {
            $this->generateStoreCss($storeId);
        }
    }
    
    protected function generateStoreCss($storeId)
    {
        $store = $this->_storeManager->getStore($storeId);
        if (!$store->isActive()) {
            return;
        }
        
        $storeCode = $store->getCode();
        $str1 = '_'.$storeCode;
        $str2 = 'menu'.$str1.'.css';
        $str3 = $this->_cssfolder->getCssDir().$str2;
        $this->_coreRegistry->register('cssgen_store', $storeCode);

        try {
            $block = $this->_layoutManager->createBlock('Rootways\Megamenu\Block\Design')
            ->setData('area', 'frontend')->setTemplate("Rootways_Megamenu::html/design.phtml")->toHtml();
            if (!$this->driverFile->isExists($this->_cssfolder->getCssDir())) {
                $this->_io->mkdir($this->_cssfolder->getCssDir(), 0777);
            }
            $file = fopen($str3, "w+");
            flock($file, LOCK_EX);
            fwrite($file, $block);
            flock($file, LOCK_UN);
            fclose($file);
            if (empty($block)) {
                throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase(__("Rootways Megamenu template file is empty or doesn't exist: ")));
            }
        } catch (\Exception $e) {
            $this->_messageManager->addError(__('Failed generating CSS file: '.$str2.' in '.
            $this->_cssfolder->getCssDir()).'<br/>Message: '.$e->getMessage());
        }
        $this->_coreRegistry->unregister('cssgen_store');
    }
}

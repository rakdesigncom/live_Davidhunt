<?php
namespace Rootways\Megamenu\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class TopMenu implements ArgumentInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $_requiredSettings;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig($config_path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getRequiredSettings($storeId = null)
    {
        if (!$this->_requiredSettings) {
            $settingCollection = [
                'is_active' => $this->scopeConfig->getValue(
                    'rootmegamenu_option/general/enable',
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                'show_home' => $this->scopeConfig->getValue(
                    'rootmegamenu_option/general/show_home_link',
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                'show_contactus' => $this->scopeConfig->getValue(
                    'rootmegamenu_option/general/show_contactus',
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                'show_viewmore' => $this->scopeConfig->getValue(
                    'rootmegamenu_option/general/show_view_more',
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                'topmenu_icon' => $this->scopeConfig->getValue(
                    'rootmegamenu_option/general/topmenu_icon',
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                'topmenuarrow' => $this->scopeConfig->getValue(
                    'rootmegamenu_option/general/topmenuarrow',
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                'custom_link' =>  $this->scopeConfig->getValue(
                    'rootmegamenu_option/general/custom_link',
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                'show_social_share' =>  $this->scopeConfig->getValue(
                    'rootmegamenu_option/general/show_social_share_icon',
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                'topmenualignmenttype' => $this->scopeConfig->getValue(
                    'rootmegamenu_option/general/topmenualignmenttype',
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                )
            ];

            $this->_requiredSettings = $settingCollection;
        }

        return $this->_requiredSettings;
    }
}

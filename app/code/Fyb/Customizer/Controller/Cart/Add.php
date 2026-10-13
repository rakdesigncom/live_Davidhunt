<?php

namespace Fyb\Customizer\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\LocalizedToNormalized;
use \Magento\Checkout\Model\Cart;

class Add extends \Magento\Checkout\Controller\Cart\Add implements HttpGetActionInterface, HttpPostActionInterface
{
    const IMG_PATH = 'catalog/product/';

    /**
     * @var RequestQuantityProcessor
     */
    private $quantityProcessor;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    private $fileReader;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $fileReader,
        ?RequestQuantityProcessor $quantityProcessor = null
    ) {
        parent::__construct(
            $context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart, $productRepository,
            $quantityProcessor
        );

        $this->filesystem = $filesystem;
        $this->fileReader = $fileReader;
        $this->productRepository = $productRepository;
        $this->quantityProcessor = $quantityProcessor
            ?? ObjectManager::getInstance()->get(RequestQuantityProcessor::class);
    }

    protected function _initProduct()
    {
        $productId = (string)$this->getRequest()->getParam('base');
        if ($productId) {
            $storeId = $this->_objectManager->get(
                \Magento\Store\Model\StoreManagerInterface::class
            )->getStore()->getId();
            try {
                return $this->productRepository->get($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    protected function saveGeneratedImage($imageUrl, $filename)
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $imagePathInfo = pathinfo(parse_url($imageUrl)["path"]);
        $dispersionPath = '/x/z';
        $filename = $filename . '.' . $imagePathInfo['extension'];
        $dirPath = $mediaDir->getAbsolutePath('catalog/product' . $dispersionPath);
        $imgPath = $dirPath . '/' . $filename;

        $imageUrl = str_replace('uat.', '', $imageUrl);
        $imageUrl = str_replace('http:', 'https:', $imageUrl);

        $this->fileReader->checkAndCreateFolder($dirPath);
        if (!$this->fileReader->fileExists($imgPath)) {
            $result = $this->fileReader->read($imageUrl, $imgPath);
        }


        $pos = strpos($imgPath, $dispersionPath);
        $imgPath = substr($imgPath, $pos);

        return $imgPath;
    }

    protected function prepareOptions($customSku, $options, $noteField)
    {
//        $noteField = explode('|', $noteField);
//        unset($noteField[0]);
//        foreach ($noteField as &$field) {
//            $field = explode(': ', $field)[1] ?? '';
//            if ($field) {
//                $field = explode('-', $field);
//                $field = trim(trim(($field[1] ?? '')) . ' ' . $field[0]);
//            }
//        }

        $newOptions = [];
        foreach ($options as $key1 => $option) {
            if ($option['o'] == 'Tier 1') {
                $add = true;
                foreach ($options as $key2 => $option2) {
                    if ($key1 != $key2 && $option['n'] == $option2['n']) {
                        $add = false;
                        break;
                    }
                }

                if (!$add) {
                    continue;
                }
            }
            $newOptions[] = [
                'label' => $option['o'],
                'value' => $option['n'],
            ];
        }
        $newOptions[] = [
            'label' => __('Customization SKU'),
            'value' => $customSku,
        ];

        return $newOptions;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        try {
            if (isset($params['qty'])) {
                $filter = new LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get(
                        \Magento\Framework\Locale\ResolverInterface::class
                    )->getLocale()]
                );
                $params['qty'] = $this->quantityProcessor->prepareQuantity($params['qty']);
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();

            /** Check product availability */
            if (!$product) {
                return $this->goBack();
            }


            $productOptions = $this->prepareOptions($params['sku'], $params['options'], $params['info'] ?? '');
            $productOptions = json_encode($productOptions);
            $product->addCustomOption('additional_options', $productOptions);

            $rrpPrice = $params['price'];
            $imgPath = $this->saveGeneratedImage($params['image'], md5($productOptions));
            $product->setCustomizerImg($imgPath);
            $product->setCustomizerPrice($rrpPrice / 120 * 100);

            $this->cart->addProduct($product, $params);
            $this->cart->save();

            /**
             * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
             */
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            return $this->goBack($this->getCartUrl());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage(
                    $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage(
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($message)
                    );
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);
            if (!$url) {
                $url = $this->_redirect->getRedirectUrl($this->getCartUrl());
            }

            return $this->goBack($this->_redirect->getRedirectUrl($this->getCartUrl()));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            return $this->goBack();
        }
    }

    private function getCartUrl()
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true, '_query' => ['reloadcartsection' => 1]]);
    }

    private function shouldRedirectToCart()
    {
        return $this->_scopeConfig->isSetFlag(
            'checkout/cart/redirect_to_cart',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}

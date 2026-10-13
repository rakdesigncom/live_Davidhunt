<?php

namespace Rakdesign\Configurator\Controller\Cart;

class Add extends \Magento\Framework\App\Action\Action {

    protected $formKey;
    protected $cart;
    protected $product;
    protected $request;
    protected $resultJsonFactory;

    public function __construct(
            \Magento\Framework\App\Action\Context $context,
            \Magento\Framework\Data\Form\FormKey $formKey,
            \Magento\Checkout\Model\Cart $cart,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Magento\Catalog\Model\ProductFactory $product,
            array $data = []) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->product = $product;


        parent::__construct($context);
    }

    public function execute() {
        $resultJson = $this->resultJsonFactory->create();
        $post = $this->getRequest()->getPostValue();

        if (!isset($post['product']) || !isset($post['qty'])) {
            return $resultJson->setData(['status' => false, 'error' => 'no product']);
        }
        try {
            $tosave = false;
            foreach ($post['product'] as $key => $item) {
                $params = array(
                    'form_key' => $this->formKey->getFormKey(),
                    'product' => (int) $item,
                    'qty' => $post['qty'][$key]
                );
//var_dump($key,$params);
                $_product = $this->product->create()->load((int) $item);
                if (!$_product) {
                    return $resultJson->setData(['status' => false, 'error' => 'no product']);
                }
                $this->cart->addProduct($_product, $params);



                /* if ($this->cart->save()) {
                  return $resultJson->setData(['status' => false]);
                  } */
            }
            $this->cart->save();
        } catch (Exception $e) {
            return $resultJson->setData(['status' => false, 'error' => $e->getMessage()]);
        }

        return $resultJson->setData(['status' => true]);
    }

}

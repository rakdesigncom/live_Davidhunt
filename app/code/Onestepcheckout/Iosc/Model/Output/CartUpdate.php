<?php
/**
 * OneStepCheckout
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to One Step Checkout AS software license.
 *
 * License is available through the world-wide-web at this URL:
 * https://www.onestepcheckout.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to mail@onestepcheckout.com so we can send you a copy immediately.
 *
 * @category   onestepcheckout
 * @package    onestepcheckout_iosc
 * @copyright  Copyright (c) 2017 OneStepCheckout  (https://www.onestepcheckout.com/)
 * @license    https://www.onestepcheckout.com/LICENSE.txt
 */
namespace Onestepcheckout\Iosc\Model\Output;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Onestepcheckout\Iosc\Helper\Data;

/**
 * CartUpdate implements OutputManagementInterface
 */
class CartUpdate implements OutputManagementInterface
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Get outputKey
     *
     * @return string
     */
    public function getOutputKey()
    {
        return 'updateqty';
    }

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param Session $checkoutSession
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        Session $checkoutSession
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     *
     * @see \Onestepcheckout\Iosc\Model\Input\InputManagement::processPayload()
     */
    public function processPayload($input)
    {

        $data = [];

        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        if ($quote->getId()) {
            $input = $input[$this->getOutputKey()] ?? false;

            if (isset($input['item_id']) && isset($input['qty'])) {
                if ((double)$input['qty'] > 0) {
                    if ($item = $quote->getItemById((int)$input['item_id'])) {
                        $item->setQty((double)$input['qty']);
                        if ($item->getHasError()) {
                            throw new LocalizedException(__($item->getMessage()));
                        }
                    }
                } else {
                    $quote->removeItem((int)$input['item_id']);
                    if ($quote->hasItems() == 0) {
                        $quote
                            ->setTotalsCollectedFlag(false)
                            ->collectTotals();
                    }
                }

                $data['cart_items'] = (int)$quote->hasItems();
            }
        }
        return $data;
    }
}

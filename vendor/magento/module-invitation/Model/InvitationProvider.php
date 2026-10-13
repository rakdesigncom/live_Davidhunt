<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class for providing invitation by request.
 */
class InvitationProvider
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var InvitationFactory
     */
    protected $invitationFactory;

    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;

    /**
     * @param \Magento\Framework\Registry $registry
     * @param InvitationFactory $invitationFactory
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        InvitationFactory $invitationFactory,
        \Magento\Framework\Url\DecoderInterface $urlDecoder
    ) {
        $this->registry = $registry;
        $this->invitationFactory = $invitationFactory;
        $this->urlDecoder = $urlDecoder;
    }

    /**
     * Retrieve invitation
     *
     * @param RequestInterface $request
     * @return Invitation
     * @throws InputException|LocalizedException
     */
    public function get(RequestInterface $request)
    {
        if (!$this->registry->registry('current_invitation')) {
            /** @var Invitation $invitation */
            $invitation = $this->invitationFactory->create();
            $invitation->loadByInvitationCode(
                $this->urlDecoder->decode(
                    $request->getParam('invitation', false)
                )
            )->makeSureCanBeAccepted();
            $this->registry->register('current_invitation', $invitation);
        }
        $invitationData = $this->registry->registry('current_invitation');
        if (!empty($email = $request->getParam('email', '')) && $email !== $invitationData->getEmail()) {
            throw new InputException(__('Provided email does not match with invited email.'));
        }
        return $invitationData;
    }
}

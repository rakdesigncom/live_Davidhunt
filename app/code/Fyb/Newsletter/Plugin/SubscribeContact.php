<?php

namespace Fyb\Newsletter\Plugin;

use Magento\Contact\Controller\Index\Post;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class SubscribeContact
{
    /**
     * @var \Fyb\Newsletter\Model\SubscribeCustomer
     */
    protected $subscribeCustomer;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @param \Fyb\Newsletter\Model\SubscribeCustomer $subscribeCustomer
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Fyb\Newsletter\Model\SubscribeCustomer $subscribeCustomer,
        MessageManagerInterface $messageManager,
    ) {
        $this->subscribeCustomer = $subscribeCustomer;
        $this->messageManager = $messageManager;
    }

    public function subscribe($email)
    {
        try {
            $subscriber = $this->subscribeCustomer->subscribe($email);
            $message = $this->subscribeCustomer->getSuccessMessage((int)$subscriber->getSubscriberStatus());
            $this->messageManager->addSuccessMessage($message);
        } catch (LocalizedException $e) {
            $this->messageManager->addComplexErrorMessage(
                'localizedSubscriptionErrorMessage',
                ['message' => $e->getMessage()]
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong with the subscription.'));
        }
    }

    /**
     * @param Post $subject
     * @param mixed $result
     *
     * @return mixed
     */
    public function afterExecute(Post $subject, $result)
    {
        if ($subject->getRequest()->getPost('mailing_list') === 'on') {
            $this->subscribe((string)$subject->getRequest()->getPost('email'));
        }

        return $result;
    }
}

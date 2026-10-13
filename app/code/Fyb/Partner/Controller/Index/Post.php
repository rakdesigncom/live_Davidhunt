<?php

namespace Fyb\Partner\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;

class Post extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var MailInterface
     */
    protected $mail;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Fyb\Newsletter\Model\SubscribeCustomer
     */
    protected $subscribeCustomer;

    /**
     * @param Context $context
     * @param \Fyb\Partner\Model\Mail $mail
     * @param DataPersistorInterface $dataPersistor
     * @param \Fyb\Newsletter\Model\SubscribeCustomer $subscribeCustomer
     * @param null|\Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        \Fyb\Partner\Model\Mail $mail,
        DataPersistorInterface $dataPersistor,
        \Fyb\Newsletter\Model\SubscribeCustomer $subscribeCustomer,
        LoggerInterface $logger = null
    ) {
        parent::__construct($context);
        $this->mail = $mail;
        $this->dataPersistor = $dataPersistor;
        $this->subscribeCustomer = $subscribeCustomer;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    public function subscribe()
    {
        $email = (string)$this->getRequest()->getPost('email');
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
     * Post user question
     *
     * @return Redirect
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        try {
            $this->sendEmail(
                $this->prepareParams($this->validatedParams())
            );
            if ($this->getRequest()->getPost('mailing_list') === 'on') {
                $this->subscribe();
            }

            $this->messageManager->addSuccessMessage(
                __('Thanks for contacting us with your comments. We\'ll respond to you very soon.')
            );
            $this->dataPersistor->clear('partner_us');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('partner_us', $this->getRequest()->getParams());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(
                __('An error occurred while processing your form. Please try again later.')
            );
            $this->dataPersistor->set('partner_us', $this->getRequest()->getParams());
        }

        return $this->resultRedirectFactory->create()->setPath('partner/index');
    }

    /**
     * Method to send email.
     *
     * @param array $post Post data from contact form
     *
     * @return void
     */
    private function sendEmail($post)
    {
        $this->mail->send(
            $post['email'],
            ['data' => new DataObject($post)]
        );
    }

    private function prepareParams($params)
    {
        $paramsBool = [
            'have_showroom',
            'previous_purchase',
            'sell_online'
        ];
        $notRequired = [
            'company',
            'street_address_2',
            'mobile_no',
            'website_address',
            'company_reg_no',
            'vat_reg_no',
            'nature_business',
            'trade_suppliers',
            'where_sell_online'
        ];
        $canBeOther = [
            'what_action',
            'hear_about',
            'nature_business',
        ];

        foreach ($params as $key => $param) {
            $params[$key] = trim($param);
        }

        foreach ($paramsBool as $param) {
            $params[$param] = (bool)($params[$param] ?? false) ? 'Yes': 'No';
        }

        foreach ($canBeOther as $param) {
            if (!empty($params['other_' . $param]) && trim($params['other_' . $param])) {
                $params[$param] = trim($params['other_' . $param]);
            }
        }

        foreach ($notRequired as $param) {
            if (!isset($params[$param])) {
                $params[$param] = '';
            }
        }

        return $params;
    }

    /**
     * Method to validated params.
     *
     * @return array
     * @throws \Exception
     */
    private function validatedParams()
    {
        $request = $this->getRequest();

        $paramsValidate = [
            'name',
            'street_address',
            'city',
            'county',
            'telephone',
            'email',
            'count_employees',
            'have_showroom',
            'previous_purchase',
            'sell_online',
            'postcode'
        ];

        foreach ($paramsValidate as $param) {
            if (trim($request->getParam($param, '')) === '') {
                throw new LocalizedException(__('Please enter a valid params.'));
            }
        }

        if (\strpos($request->getParam('email', ''), '@') === false) {
            throw new LocalizedException(__('The email address is invalid. Verify the email address and try again.'));
        }
        if (trim($request->getParam('hideit', '')) !== '') {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new \Exception();
        }

        return $request->getParams();
    }
}

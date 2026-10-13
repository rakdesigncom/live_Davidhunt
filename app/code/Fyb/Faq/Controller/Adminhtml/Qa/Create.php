<?php

namespace Fyb\Faq\Controller\Adminhtml\Qa;

use Fyb\Faq\Controller\Adminhtml\Qa;

class Create extends Qa
{
    public function execute()
    {
        $this->_forward('edit');
    }
}

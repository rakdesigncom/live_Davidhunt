<?php

namespace Fyb\Theme\Framework\View\Result;

class Page extends \Magento\Framework\View\Result\Page
{
    /**
     * @return string
     * @throws \Exception
     */
    public function renderHtml()
    {
        $this->pageConfig->publicBuild();
        if ($this->getPageLayout()) {
            $config = $this->getConfig();
            $this->addDefaultBodyClasses();
            $addBlock = $this->getLayout()->getBlock('head.additional');
            $requireJs = $this->getLayout()->getBlock('require.js');

            $this->assign([
                'requireJs' => $requireJs ? $requireJs->toHtml() : null,
                'headContent' => $this->pageConfigRenderer->renderHeadContent(),
                'headAdditional' => $addBlock ? $addBlock->toHtml() : null,
                'htmlAttributes' => $this->pageConfigRenderer->renderElementAttributes($config::ELEMENT_TYPE_HTML),
                'headAttributes' => $this->pageConfigRenderer->renderElementAttributes($config::ELEMENT_TYPE_HEAD),
                'bodyAttributes' => $this->pageConfigRenderer->renderElementAttributes($config::ELEMENT_TYPE_BODY),
                'loaderIcon' => $this->getViewFileUrl('images/loader-2.gif'),
            ]);

            $output = $this->getLayout()->getOutput();
            $this->assign('layoutContent', $output);
            $output = $this->renderPage();

            $this->translateInline->processResponseBody($output);

            return $output;
        }

        return '';
    }
}

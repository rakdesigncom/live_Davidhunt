<?php

namespace Fyb\Blog\Plugin;

use Magento\Framework\Phrase;
use Mageplaza\Blog\Block\Frontend;
use Mageplaza\Blog\Helper\Data as HelperData;

class PostInfo
{
    protected $helperData;

    public function __construct(
        HelperData $helperData,
    ){
        $this->helperData = $helperData;
    }

    /**
     * @param Frontend $subject
     * @param callable $proceed
     * @param Post $post
     *
     * @return string
     */
    public function aroundGetPostInfo(Frontend $subject, callable $proceed, $post)
    {
        try {
            $html = $subject->getDateFormat($post->getPublishDate());

            $author = $this->helperData->getAuthorByPost($post);
            if ($author && $author->getName() && $this->helperData->showAuthorInfo()) {
                $html .= ' / By ' . $subject->escapeHtml($author->getName());
            }
        } catch (\Exception $e) {
            $html = '';
        }

        return $html;
    }
}

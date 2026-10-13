<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2023 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Model\Utils;

use Magento\Framework\Exception\LocalizedException;

/**
 * @api
 * @since 2.11.0
 */
class HtmlTag
{

    /**
     * Get first tag from html.
     *
     * @param string $tagName
     * @param string $html
     * @return string
     */
    public function get(string $tagName, string $html): string
    {
        if ($this->isSelfClosingTag($tagName)) {
            if (! preg_match("/<$tagName>|<$tagName\s[^>]*?>/", $html, $results)) {
                return '';
            }
            return $results[0];
        }

        if (! preg_match("/(<$tagName>|<$tagName\s[^>]*?>)[\s\S]*?<\/$tagName>/", $html, $results)) {
            return '';
        }
        return $results[0];
    }

    /**
     * Get all image tags from html.
     *
     * @param string $tagName
     * @param string $html
     * @return array
     */
    public function getAll(string $tagName, string $html): array
    {
        if ($this->isSelfClosingTag($tagName)) {
            if (! preg_match_all("/<$tagName>|<$tagName\s[^>]*?>/", $html, $results)) {
                return [];
            }
            return $results[0];
        }

        if (! preg_match_all("/(<$tagName>|<$tagName\s[^>]*?>)[\s\S]*?<\/$tagName>/", $html, $results)) {
            return [];
        }
        return $results[0];
    }

    /**
     * Get tags with specific content.
     *
     * @param string $tagName
     * @param string $html
     * @param string $tagContent
     * @return array|string[]
     */
    public function getAllWithContent(string $tagName, string $html, string $tagContent): array
    {
        $tags = $this->getAll($tagName, $html);
        $result = [];
        foreach ($tags as $tag) {
            if (false !== strpos($tag, $tagContent)) {
                $result[] = $tag;
            }
        }
        return $result;
    }

    /**
     * Check if tag is exist in html.
     *
     * @param string $tagName
     * @param string $html
     * @return bool
     */
    public function isExistElement(string $tagName, string $html): bool
    {
        return (bool) $this->get($tagName, $html);
    }

    /**
     * Parse html
     *
     * @param string $tagName
     * @param string $html
     * @return string
     */
    public function getTagContent(string $tagName, string $html): string
    {
        preg_match('#<' .$tagName . '[\s\S]*?>([^`]*?)<\/' . $tagName . '>#', $html, $matches);
        return (string) $matches[1];
    }

    /**
     * Parse html
     *
     * @param string $tagName
     * @param string $html
     * @return array
     */
    public function getTagContents(string $tagName, string $html): array
    {
        preg_match_all('#<' .$tagName . '[\s\S]*?>([^`]*?)<\/' . $tagName . '>#', $html, $matches);
        return $matches[1];
    }

    /**
     * Check if tag is self-closing.
     *
     * @param string $tagName
     * @return bool
     */
    public function isSelfClosingTag(string $tagName): bool
    {
        return in_array(
            $tagName,
            [
                'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
                'link', 'meta', 'param', 'source', 'track', 'wbr',
            ],
            true
        );
    }

    /**
     * Cut tag from HTML.
     *
     * @param string $tagName
     * @param string $html
     * @return array
     */
    public function cut(string $tagName, string $html): array
    {
        $tag = $this->get($tagName, $html);
        if (! $tag) {
            return ['', $html];
        }
        $html = str_replace($tag, '', $html);
        return [$tag, $html];
    }

    /**
     * Cut tags from HTML.
     *
     * @param string $tagName
     * @param string $html
     * @return array
     */
    public function cutAll(string $tagName, string $html): array
    {
        $tags = $this->getAll($tagName, $html);
        if (! $tags) {
            return [[], $html];
        }
        $html = str_replace($tags, '', $html);
        return [$tags, $html];
    }

    /**
     * Cut first tag with specific content.
     *
     * @param string $tagName
     * @param string $html
     * @param string $tagContent
     * @return array|string[]
     */
    public function cutWithContent(string $tagName, string $html, string $tagContent): array
    {
        $tags = $this->getAll($tagName, $html);
        foreach ($tags as $tag) {
            if (false !== strpos($tag, $tagContent)) {
                $html = str_replace($tag, '', $html);
                return [$tag, $html];
            }
        }
        return ['', $html];
    }

    /**
     * Cut tags with specific content.
     *
     * @param string $tagName
     * @param string $html
     * @param string $tagContent
     * @return array|string[]
     */
    public function cutAllWithContent(string $tagName, string $html, string $tagContent): array
    {
        $tags = $this->getAll($tagName, $html);
        $result = [];
        foreach ($tags as $tag) {
            if (false !== strpos($tag, $tagContent)) {
                $html = str_replace($tag, '', $html);
                $result[] = $tag;
            }
        }
        return [$result, $html];
    }

    /**
     * Insert a html string before the first child of the tag.
     *
     * @param string $html
     * @param string $tagName
     * @param string $htmlString
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepend(string $html, string $tagName, string $htmlString): string
    {
        if ($this->isSelfClosingTag($tagName)) {
            throw new LocalizedException(__('Cannot prepend html for self-closing tag %1.', $tagName));
        }

        if (! $this->isExistElement($tagName, $html)) {
            throw new LocalizedException(__('Cannot find element %1 in which we have to prepend html.', $tagName));
        }

        if (! $htmlString) {
            return $html;
        }
        return preg_replace("/(<$tagName>|<$tagName\s[^>]*?>)/", "$1\n$htmlString", $html);
    }

    /**
     * Insert a html string after the last child of the tag.
     *
     * @param string $html
     * @param string $tagName
     * @param string $htmlString
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function append(string $html, string $tagName, string $htmlString): string
    {
        if ($this->isSelfClosingTag($tagName)) {
            throw new LocalizedException(__('Cannot append html for self-closing tag %1.', $tagName));
        }

        if (! $this->isExistElement($tagName, $html)) {
            throw new LocalizedException(__('Cannot find element %1 in which we have to append html.', $tagName));
        }

        if (! $htmlString) {
            return $html;
        }
        return str_replace("</$tagName>", "\n$htmlString\n</$tagName>", $html);
    }

    /**
     * Remove empty lines from html.
     *
     * @param string $html
     * @return string
     */
    public function removeEmptyLines(string $html): string
    {
        return preg_replace('/\n{2,}/', '', $html);
    }

    /**
     * Get attribute value.
     *
     * @param string $tagHtml
     * @param string $attributeName
     * @param bool   $useDomDocument
     * @return string
     */
    public function getAttribute(string $tagHtml, string $attributeName, bool $useDomDocument = true): string
    {
        if (! $tagHtml || false === strpos($tagHtml, $attributeName)) {
            return '';
        }

        $tagName = $this->getTagName($tagHtml);
        if (! $tagName) {
            return '';
        }

        if (! $useDomDocument || in_array($tagName, ['source', 'section', 'figure'], true)) {
            return $this->getAttributeByRegex($tagHtml, $attributeName, $tagName);
        }

        try {
            $doc = new \DOMDocument();
            $doc->loadHTML($tagHtml);
            /** @var \DOMElement $element */
            $element = $doc->getElementsByTagName($tagName)[0];
            return $element->getAttribute($attributeName);
        } catch (\Exception $e) {
            return $this->getAttributeByRegex($tagHtml, $attributeName, $tagName);
        }
    }

    /**
     * Get attribute value by regex.
     *
     * @param string $tagHtml
     * @param string $attributeName
     * @param string $tagName
     * @return string
     */
    private function getAttributeByRegex(string $tagHtml, string $attributeName, string $tagName = ''): string
    {
        $tagName = $tagName ?: $this->getTagName($tagHtml);
        $found = preg_match(
            "/<{$tagName}[\s\S]*?\s$attributeName\s*?=\s*?['\"]([\s\S]*?)['\"][\s\S]*?>/",
            $tagHtml,
            $result
        );
        return $found ? trim((string) $result[1]) : '';
    }

    /**
     * Get attributes from tag.
     *
     * @param string $tagHtml
     * @param array  $attributes
     * @return array
     */
    public function getAttributes(string $tagHtml, array $attributes): array
    {
        if (! $tagHtml) {
            return array_fill_keys($attributes, '');
        }

        $tagName = $this->getTagName($tagHtml);

        try {
            $doc = new \DOMDocument();
            $doc->loadHTML($tagHtml);
            /** @var \DOMElement $element */
            $element = $doc->getElementsByTagName($tagName)[0];
            $result = [];
            foreach ($attributes as $attributeName) {
                $result[$attributeName] = $element->getAttribute($attributeName);
            }
            return $result;
        } catch (\Exception $e) {
            $result = [];
            foreach ($attributes as $attributeName) {
                $result[$attributeName] = $this->getAttributeByRegex($tagHtml, $attributeName, $tagName);
            }
            return $result;
        }
    }

    /**
     * Set attribute value to tag.
     *
     * @param string $tagHtml
     * @param string $attributeName
     * @param string $value
     * @return string
     */
    public function setAttribute(string $tagHtml, string $attributeName, string $value): string
    {
        if ($this->getAttribute($tagHtml, $attributeName)) {
            $tagHtml = preg_replace(
                "/(<\S[\s\S]*?)\s$attributeName\s*?=\s*?['\"][\s\S]*?['\"]([\s\S]*?>)/",
                "$1 $attributeName=\"$value\"$2",
                $tagHtml
            );
        } else {
            $tagName = $this->getTagName($tagHtml);
            $tagHtml = preg_replace("/^<$tagName/", "<$tagName $attributeName=\"$value\"", $tagHtml);
        }
        return $tagHtml;
    }

    /**
     * Get name of first tag in html.
     *
     * @param string $tagHtml
     * @return string
     */
    public function getTagName(string $tagHtml): string
    {
        if (! preg_match('/(?:<(\w+?)\s|<(\S+?)>)/', $tagHtml, $result)) {
            return '';
        }
        return $result[1] ?: $result[2];
    }

    /**
     * Remove attribute from tag.
     *
     * @param string $tagHtml
     * @param string $attributeName
     * @return string
     */
    public function removeAttribute(string $tagHtml, string $attributeName): string
    {
        if (! $this->getAttribute($tagHtml, $attributeName, false)) {
            if (false === strpos($tagHtml, $attributeName)) {
                return $tagHtml;
            }
            $tagHtml = $this->removeEmptyAttribute($tagHtml, $attributeName);
            return $this->removeAttributeWithoutValue($tagHtml, $attributeName);
        }
        return $this->removeExistingAttribute($tagHtml, $attributeName);
    }

    /**
     * Join two parts of the tag nicely.
     *
     * @param string $tagHtml
     * @param string $beginning
     * @param string $end
     * @return string
     */
    private function joinTag(string $tagHtml, string $beginning, string $end): string
    {
        $beginning = trim($beginning);
        $end = trim($end, "\t\n\r");
        $lineCount = substr_count($tagHtml, "\n");
        if (substr_count($beginning, "\n") + substr_count($end, "\n") !== $lineCount) {
            return "$beginning\n$end";
        }
        if (0 === strpos($end, '>')) {
            return "$beginning$end";
        }
        return "$beginning $end";
    }

    /**
     * Remove attributes like src in <img src="">.
     *
     * @param string $tagHtml
     * @param string $attributeName
     * @return string
     */
    private function removeEmptyAttribute(string $tagHtml, string $attributeName): string
    {
        return $this->removeExistingAttribute($tagHtml, $attributeName);
    }

    /**
     * Remove attributes like required in <input required>.
     *
     * @param string $tagHtml
     * @param string $attributeName
     * @return string
     */
    private function removeAttributeWithoutValue(string $tagHtml, string $attributeName): string
    {
        preg_match("/(<\S[\s\S]*?)\s$attributeName\s*?([\s\S]*?>)/", $tagHtml, $result);
        if (empty($result[1]) || empty($result[2])) {
            return $tagHtml;
        }
        return $this->joinTag($tagHtml, $result[1], $result[2]);
    }

    /**
     * Do not check if attribute is exists.
     *
     * @param string $tagHtml
     * @param string $attributeName
     * @return string
     */
    private function removeExistingAttribute(string $tagHtml, string $attributeName): string
    {
        if (false === strpos($tagHtml, "\n")) {
            return preg_replace(
                "/(<\S[\s\S]*?)\s$attributeName\s*?=\s*?['\"][\s\S]*?['\"]([\s\S]*?>)/",
                "$1$2",
                $tagHtml
            );
        }
        preg_match("/(<\S[\s\S]*?)\s$attributeName\s*?=\s*?['\"][\s\S]*?['\"]([\s\S]*?>)/", $tagHtml, $result);
        if (empty($result[1]) || empty($result[2])) {
            return $tagHtml;
        }
        return $this->joinTag($tagHtml, $result[1], $result[2]);
    }
}

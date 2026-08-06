<?php
/**
 * Mageprince
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the mageprince.com license that is
 * available through the world-wide-web at this URL:
 * https://mageprince.com/end-user-license-agreement
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageprince
 * @package     Mageprince_MageAI
 * @copyright   Copyright (c) Mageprince (https://mageprince.com/)
 * @license     https://mageprince.com/end-user-license-agreement
 */

namespace Mageprince\MageAI\Model\CategoryData;

/**
 * Replaces the category prompt variables with the values submitted from the category form.
 *
 * Shared by the category image generation and modification controllers so both support the
 * same variable set.
 */
class PromptRenderer
{
    /**
     * Maximum number of description characters passed to the AI
     *
     * Category descriptions can be long HTML documents; only the leading plain text is useful
     * as image context and keeping it short also keeps the request cheap.
     */
    private const DESCRIPTION_MAX_LENGTH = 600;

    /**
     * Replace the supported variables in a prompt template
     *
     * @param string $template
     * @param string $name Category name
     * @param string $description Category description (may contain HTML)
     * @return string
     */
    public function render(string $template, string $name, string $description): string
    {
        return str_replace(
            ['{{ category.name }}', '{{ category.description }}'],
            [trim($name), $this->normalizeDescription($description)],
            $template
        );
    }

    /**
     * Reduce a category description to a short plain-text snippet
     *
     * @param string $description
     * @return string
     */
    private function normalizeDescription(string $description): string
    {
        $text = strip_tags($description);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        if (mb_strlen($text) > self::DESCRIPTION_MAX_LENGTH) {
            $text = rtrim(mb_substr($text, 0, self::DESCRIPTION_MAX_LENGTH)) . '…';
        }

        return $text;
    }
}

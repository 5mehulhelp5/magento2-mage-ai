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

namespace Mageprince\MageAI\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class GeminiModel implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'gemini-3.8-flash', 'label' => 'gemini-3.8-flash'],
            ['value' => 'gemini-3.7-flash', 'label' => 'gemini-3.7-flash'],
            ['value' => 'gemini-3.6-flash', 'label' => 'gemini-3.6-flash'],
            ['value' => 'gemini-3.5-flash', 'label' => 'gemini-3.5-flash'],
            ['value' => 'gemini-3.5-flash-lite', 'label' => 'gemini-3.5-flash-lite'],
            ['value' => 'gemini-3.1-flash-lite', 'label' => 'gemini-3.1-flash-lite'],
            ['value' => 'gemini-2.5-pro', 'label' => 'gemini-2.5-pro'],
            ['value' => 'gemini-2.5-flash', 'label' => 'gemini-2.5-flash'],
            ['value' => 'gemini-2.5-flash-lite', 'label' => 'gemini-2.5-flash-lite'],
        ];
    }
}

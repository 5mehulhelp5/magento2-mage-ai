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

class AIModel implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'gpt-6-astra', 'label' => 'gpt-6-astra'],
            ['value' => 'gpt-5.6-sol', 'label' => 'gpt-5.6-sol'],
            ['value' => 'gpt-5.6-terra', 'label' => 'gpt-5.6-terra'],
            ['value' => 'gpt-5.6-luna', 'label' => 'gpt-5.6-luna'],
            ['value' => 'gpt-4o', 'label' => 'gpt-4o'],
            ['value' => 'gpt-4o-mini', 'label' => 'gpt-4o-mini'],
        ];
    }
}

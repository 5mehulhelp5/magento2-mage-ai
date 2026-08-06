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

namespace Mageprince\MageAI\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Mageprince\MageAI\Helper\Data as HelperData;
use Mageprince\MageAI\Model\CategoryData\PromptRenderer;
use Mageprince\MageAI\Model\Query\ImageGeneration;
use Mageprince\MageAI\Model\Query\QueryException;

class GenerateImage extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Mageprince_MageAI::generate';

    /**
     * @var JsonFactory
     */
    protected $resultJson;

    /**
     * @var ImageGeneration
     */
    protected $imageGeneration;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var PromptRenderer
     */
    protected $promptRenderer;

    /**
     * @param Action\Context $context
     * @param JsonFactory $resultJson
     * @param ImageGeneration $imageGeneration
     * @param HelperData $helper
     * @param PromptRenderer $promptRenderer
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJson,
        ImageGeneration $imageGeneration,
        HelperData $helper,
        PromptRenderer $promptRenderer
    ) {
        $this->resultJson = $resultJson;
        $this->imageGeneration = $imageGeneration;
        $this->helper = $helper;
        $this->promptRenderer = $promptRenderer;
        parent::__construct($context);
    }

    /**
     * Generate a category image
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $response = ['error' => true, 'data' => __('An unknown error occurred.')];

        if ($this->helper->isEnabled()) {
            try {
                $customPrompt = trim((string) $this->getRequest()->getParam('custom_prompt', ''));
                $categoryName = (string) $this->getRequest()->getParam('category_name', '');
                $categoryDescription = (string) $this->getRequest()->getParam('category_description', '');

                // Use the custom prompt when provided, otherwise the configured default.
                // Both support the {{ category.name }} and {{ category.description }} variables.
                $prompt = $customPrompt !== ''
                    ? $customPrompt
                    : $this->helper->getImageDefaultPrompt(HelperData::GROUP_CATEGORY_IMAGE);
                $prompt = $this->promptRenderer->render($prompt, $categoryName, $categoryDescription);

                $imageData = $this->imageGeneration->generate($prompt);
                return $this->resultJson->create()->setData($imageData);
            } catch (QueryException $e) {
                $response = ['error' => true, 'data' => $e->getMessage()];
            } catch (\Exception $e) {
                $response = ['error' => true, 'data' => $e->getMessage()];
            }
        }

        return $this->resultJson->create()->setData($response);
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}

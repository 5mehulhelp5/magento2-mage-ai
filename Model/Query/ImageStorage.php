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
// phpcs:disable Generic.Files.LineLength

namespace Mageprince\MageAI\Model\Query;

use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Client\CurlFactory;

/**
 * Reads and writes product images to the media directory.
 *
 * Used by both image generation and image modification so the dispersion-path
 * persistence logic lives in a single place.
 */
class ImageStorage extends AbstractImageStorage
{
    /**
     * @var MediaConfig
     */
    protected $mediaConfig;

    /**
     * @param Filesystem $filesystem
     * @param MediaConfig $mediaConfig
     * @param CurlFactory $curlFactory
     */
    public function __construct(
        Filesystem $filesystem,
        MediaConfig $mediaConfig,
        CurlFactory $curlFactory
    ) {
        $this->mediaConfig = $mediaConfig;
        parent::__construct($filesystem, $curlFactory);
    }

    /**
     * Save image binary to the product media temp directory and return gallery-compatible file data
     *
     * The format mirrors what Magento\Catalog\Controller\Adminhtml\Product\Gallery\Upload returns.
     *
     * The file is stored using Magento's standard two-level dispersion path (/m/a/filename.jpg).
     * This is required: the get.php on-the-fly resizer (MediaStorage\App\Media::getOriginalImage)
     * resolves the original image from a cache URL by taking the last three path segments,
     * so any non-dispersed path breaks frontend cache generation and shows the placeholder.
     *
     * @param string $imageData  Raw binary content
     * @param string $mimeType
     * @param string $ext
     * @return array
     * @throws QueryException
     */
    public function persist(string $imageData, string $mimeType, string $ext): array
    {
        $fileName = 'mageai_' . uniqid('', true) . '.' . $ext;

        // Standard Magento dispersion: /m/a/ for "mageai_..." filenames
        $dispersionPath = \Magento\Framework\File\Uploader::getDispersionPath($fileName);
        $fileRelativeToTmp = $dispersionPath . '/' . $fileName; // /m/a/mageai_xxx.jpg

        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            // Save under catalog/product/tmp/m/a/ so Magento moves it to the permanent
            // location (catalog/product/m/a/) when the product is saved.
            $tmpBase = $this->getTempBasePath(); // catalog/product/tmp
            $mediaDirectory->create($tmpBase . $dispersionPath);
            $mediaDirectory->writeFile($tmpBase . $fileRelativeToTmp, $imageData);
        } catch (\Exception $e) {
            throw new QueryException(__('Failed to save image: %1', $e->getMessage()));
        }

        $url = $this->mediaConfig->getTmpMediaUrl($fileRelativeToTmp);

        return [
            'name' => $fileName,
            'size' => strlen($imageData),
            'type' => $mimeType,
            'url'  => $url,
            // Appending .tmp signals Magento to move this file to the permanent directory on product save
            'file' => $fileRelativeToTmp . '.tmp',
        ];
    }

    /**
     * Read the binary content of an existing product image referenced by a gallery "file" value.
     *
     * Handles both saved images (e.g. "/m/a/foo.jpg" under catalog/product) and freshly added,
     * not-yet-saved images whose file value still carries the ".tmp" suffix (under catalog/product/tmp).
     *
     * @param string $file Gallery imageData.file value
     * @return array{data: string, mimeType: string, ext: string}
     * @throws QueryException
     */
    public function readOriginal(string $file): array
    {
        $file = trim($file);
        if ($file === '') {
            throw new QueryException(__('No source image was provided to modify.'));
        }

        $isTmp = substr($file, -4) === '.tmp';
        $relative = $isTmp ? substr($file, 0, -4) : $file;

        $mediaPath = $isTmp
            ? $this->mediaConfig->getTmpMediaPath($relative)
            : $this->mediaConfig->getMediaPath($relative);

        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            if (!$mediaDirectory->isExist($mediaPath)) {
                throw new QueryException(__('The original product image could not be found on the server.'));
            }
            $data = $mediaDirectory->readFile($mediaPath);
        } catch (QueryException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new QueryException(__('Failed to read the original product image: %1', $e->getMessage()));
        }

        if ($data === '' || $data === false) {
            throw new QueryException(__('The original product image is empty or unreadable.'));
        }

        $ext = $this->resolveExtension($relative);

        return ['data' => $data, 'mimeType' => $this->resolveMimeType($ext), 'ext' => $ext];
    }

    /**
     * @inheritDoc
     */
    protected function getTempBasePath(): string
    {
        return $this->mediaConfig->getBaseTmpMediaPath();
    }
}

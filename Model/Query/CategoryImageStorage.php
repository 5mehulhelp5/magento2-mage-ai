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

use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Reads and writes category images to the media directory.
 *
 * The generated file is written to the same temp directory the core category image uploader uses
 * (catalog/tmp/category) and the returned data mirrors the response of
 * Magento\Catalog\Controller\Adminhtml\Category\Image\Upload, so the imageUploader form element
 * accepts it and Magento\Catalog\Model\Category\Attribute\Backend\Image moves the file to
 * catalog/category on category save.
 *
 * Paths are read from the injected uploader (Magento\Catalog\CategoryImageUpload) rather than
 * hardcoded, so any project-level customisation of those directories is respected.
 */
class CategoryImageStorage extends AbstractImageStorage
{
    /**
     * @var ImageUploader
     */
    protected $imageUploader;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Database
     */
    protected $coreFileStorageDatabase;

    /**
     * @param Filesystem $filesystem
     * @param CurlFactory $curlFactory
     * @param ImageUploader $imageUploader
     * @param StoreManagerInterface $storeManager
     * @param Database $coreFileStorageDatabase
     */
    public function __construct(
        Filesystem $filesystem,
        CurlFactory $curlFactory,
        ImageUploader $imageUploader,
        StoreManagerInterface $storeManager,
        Database $coreFileStorageDatabase
    ) {
        $this->imageUploader = $imageUploader;
        $this->storeManager = $storeManager;
        $this->coreFileStorageDatabase = $coreFileStorageDatabase;
        parent::__construct($filesystem, $curlFactory);
    }

    /**
     * Save image binary to the category media temp directory and return uploader-compatible file data
     *
     * Category images are stored flat (no dispersion path) — that is what the core uploader and the
     * category image backend model expect.
     *
     * @param string $imageData Raw binary content
     * @param string $mimeType
     * @param string $ext
     * @return array
     * @throws QueryException
     */
    public function persist(string $imageData, string $mimeType, string $ext): array
    {
        $fileName = 'mageai_' . uniqid('', true) . '.' . $ext;
        $tmpBase = $this->getTempBasePath();
        $relativePath = $tmpBase . '/' . $fileName;

        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $mediaDirectory->create($tmpBase);
            $mediaDirectory->writeFile($relativePath, $imageData);
            // Keeps the file available when the database media storage is enabled
            $this->coreFileStorageDatabase->saveFile($relativePath);
        } catch (\Exception $e) {
            throw new QueryException(__('Failed to save image: %1', $e->getMessage()));
        }

        return [
            'name' => $fileName,
            'file' => $fileName,
            'size' => strlen($imageData),
            'type' => $mimeType,
            'url'  => $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $relativePath,
            // The category image backend model only moves the file out of the temp directory
            // when tmp_name is present in the submitted value.
            'tmp_name' => '/' . $relativePath,
        ];
    }

    /**
     * Read the binary content of the image currently set on the category
     *
     * The uploader form element keeps the image URL rather than a media path, and the image may
     * live in the category directory (saved), in the temp directory (generated but not saved yet)
     * or anywhere under media when it was picked from the media gallery — so every candidate
     * location is tried in turn.
     *
     * @param string $file Image URL or file name taken from the uploader value
     * @return array{data: string, mimeType: string, ext: string}
     * @throws QueryException
     */
    public function readOriginal(string $file): array
    {
        $file = trim($file);
        if ($file === '') {
            throw new QueryException(__('No source image was provided to modify.'));
        }

        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            $mediaPath = null;
            foreach ($this->buildCandidatePaths($file) as $candidate) {
                if ($mediaDirectory->isExist($candidate)) {
                    $mediaPath = $candidate;
                    break;
                }
            }

            if ($mediaPath === null) {
                throw new QueryException(__('The original category image could not be found on the server.'));
            }

            $data = $mediaDirectory->readFile($mediaPath);
        } catch (QueryException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new QueryException(__('Failed to read the original category image: %1', $e->getMessage()));
        }

        if ($data === '' || $data === false) {
            throw new QueryException(__('The original category image is empty or unreadable.'));
        }

        $ext = $this->resolveExtension($mediaPath);

        return ['data' => $data, 'mimeType' => $this->resolveMimeType($ext), 'ext' => $ext];
    }

    /**
     * @inheritDoc
     */
    protected function getTempBasePath(): string
    {
        return rtrim($this->imageUploader->getBaseTmpPath(), '/');
    }

    /**
     * Build the list of media-relative paths the given image reference may point at
     *
     * @param string $file
     * @return string[]
     */
    private function buildCandidatePaths(string $file): array
    {
        // The value can be a full URL, an absolute path or a bare file name.
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $path = (string) (parse_url($file, PHP_URL_PATH) ?: $file);
        $path = ltrim($path, '/');

        // Never let a crafted value escape the media directory
        if ($path === '' || strpos($path, '..') !== false) {
            return [];
        }

        $candidates = [];

        // Strip the media base from URLs such as /media/catalog/category/foo.jpg
        $mediaUri = trim((string) $this->filesystem->getUri(DirectoryList::MEDIA), '/');
        foreach (array_unique(array_filter([$mediaUri, 'pub/media', 'media'])) as $prefix) {
            if (strpos($path, $prefix . '/') === 0) {
                $candidates[] = substr($path, strlen($prefix) + 1);
                break;
            }
        }

        // Already media-relative (e.g. catalog/category/foo.jpg)
        $candidates[] = $path;

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $baseName = basename($path);
        // Generated by MageAI but not saved yet, then the permanent category directory
        $candidates[] = $this->getTempBasePath() . '/' . $baseName;
        $candidates[] = rtrim($this->imageUploader->getBasePath(), '/') . '/' . $baseName;

        return array_values(array_unique($candidates));
    }
}

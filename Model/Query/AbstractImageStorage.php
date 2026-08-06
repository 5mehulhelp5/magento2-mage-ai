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

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;

/**
 * Shared media plumbing for the image storages.
 *
 * Holds everything that does not depend on the target entity: the throwaway file used for
 * multipart uploads, remote downloads and mime type resolution.
 */
abstract class AbstractImageStorage implements ImageStorageInterface
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @param Filesystem $filesystem
     * @param CurlFactory $curlFactory
     */
    public function __construct(
        Filesystem $filesystem,
        CurlFactory $curlFactory
    ) {
        $this->filesystem = $filesystem;
        $this->curlFactory = $curlFactory;
    }

    /**
     * Media-relative directory that holds not-yet-saved images for the target entity
     *
     * @return string
     */
    abstract protected function getTempBasePath(): string;

    /**
     * @inheritDoc
     */
    public function writeTempFile(string $imageData, string $ext): array
    {
        $relative = 'mageai_src_' . uniqid('', true) . '.' . $ext;
        $path = $this->getTempBasePath() . '/' . $relative;

        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $mediaDirectory->writeFile($path, $imageData);
            $absolutePath = $mediaDirectory->getAbsolutePath($path);
        } catch (\Exception $e) {
            throw new QueryException(__('Failed to prepare the source image for modification: %1', $e->getMessage()));
        }

        return ['path' => $path, 'absolutePath' => $absolutePath];
    }

    /**
     * @inheritDoc
     */
    public function removeTempFile(string $path): void
    {
        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            if ($mediaDirectory->isExist($path)) {
                $mediaDirectory->delete($path);
            }
        } catch (\Exception $e) {
            // Best-effort cleanup; ignore failures so a leftover temp file never breaks the request
            return;
        }
    }

    /**
     * Download image binary from a URL using a fresh Curl instance
     *
     * A new instance is used so any API auth headers from the calling request do not leak
     * into this download.
     *
     * @param string $url
     * @return string
     * @throws QueryException
     */
    public function download(string $url): string
    {
        /** @var Curl $curl */
        $curl = $this->curlFactory->create();
        $curl->setTimeout(60);
        $curl->setOption(CURLOPT_FOLLOWLOCATION, true);

        try {
            $curl->get($url);
        } catch (\Exception $e) {
            throw new QueryException(__('Failed to download image: %1', $e->getMessage()));
        }

        $data = $curl->getBody();
        if ($curl->getStatus() >= 400 || $data === '') {
            throw new QueryException(__('Failed to download image (HTTP %1).', $curl->getStatus()));
        }

        return $data;
    }

    /**
     * Map a file extension to an image mime type
     *
     * @param string $ext
     * @return string
     */
    protected function resolveMimeType(string $ext): string
    {
        switch ($ext) {
            case 'png':
                return 'image/png';
            case 'webp':
                return 'image/webp';
            case 'gif':
                return 'image/gif';
            default:
                return 'image/jpeg';
        }
    }

    /**
     * Extract the lowercase file extension from a path, defaulting to jpg
     *
     * @param string $path
     * @return string
     */
    protected function resolveExtension(string $path): string
    {
        $dotPos = strrpos($path, '.');
        $ext = $dotPos !== false ? strtolower(substr($path, $dotPos + 1)) : '';

        return $ext !== '' ? $ext : 'jpg';
    }
}

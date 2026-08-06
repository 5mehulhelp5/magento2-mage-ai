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

namespace Mageprince\MageAI\Model\Query;

/**
 * Reads and writes AI generated images to the media directory.
 *
 * Implementations decide where the image lives and which file data format the target form
 * expects — the product gallery and the category image uploader use different conventions.
 */
interface ImageStorageInterface
{
    /**
     * Save image binary to the media temp directory and return form-compatible file data
     *
     * @param string $imageData Raw binary content
     * @param string $mimeType
     * @param string $ext
     * @return array
     * @throws QueryException
     */
    public function persist(string $imageData, string $mimeType, string $ext): array;

    /**
     * Read the binary content of an existing image referenced by a form value
     *
     * @param string $file Form file reference (gallery file value, file name or media URL)
     * @return array{data: string, mimeType: string, ext: string}
     * @throws QueryException
     */
    public function readOriginal(string $file): array;

    /**
     * Write raw image bytes to a throwaway file and return its path and absolute path
     *
     * Used to build a multipart upload (CURLFile) for APIs that require a real file handle.
     * The caller is responsible for removing the file afterwards via removeTempFile().
     *
     * @param string $imageData
     * @param string $ext
     * @return array{path: string, absolutePath: string}
     * @throws QueryException
     */
    public function writeTempFile(string $imageData, string $ext): array;

    /**
     * Remove a temporary file previously created via writeTempFile()
     *
     * @param string $path Relative-to-media path
     * @return void
     */
    public function removeTempFile(string $path): void;

    /**
     * Download image binary from a URL
     *
     * @param string $url
     * @return string
     * @throws QueryException
     */
    public function download(string $url): string;
}

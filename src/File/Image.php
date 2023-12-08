<?php

namespace AtpCore\File;

use AtpCore\BaseClass;
use finfo;
use \Gumlet\ImageResize;
use \Spatie\ImageOptimizer\OptimizerChainFactory;

class Image extends BaseClass
{

    /**
     * Convert base64-encoded image-content
     * @param string $imageContent
     * @return string
     */
    public static function convertBase64Content($imageContent)
    {
        // Check if image-content is valid base64-encoded string
        if (preg_match("/^data:image\/(bmp|jpeg|png|gif);base64,(.*)$/", $imageContent, $data)) {
            $imageContent = $data[2];
        }

        // Return
        return base64_decode($imageContent);
    }

    /**
     * Returns mime-type of content-string or file
     *
     * @param string $resource path to the file or content-string
     * @param string $type
     * @return string|false
     */
    public static function getMimeType($resource, $type = "string") {
        // Set memory
        $memory = ini_get('memory_limit');
        if ($memory < "256M") ini_set('memory_limit', '256M');

        // Get mime-type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = ($type =='string') ? $finfo->buffer($resource) : $finfo->file($resource);

        // Reset memory
        ini_set('memory_limit', $memory);

        // Return
        return $mimeType;
    }

    /**
     * Read image (content) by URL
     *
     * @param string $url
     * @return false|string
     */
    public function readImageByUrl($url)
    {
        // Strip slashes from URL (to avoid get_headers(): This function may only be used against URLs in php shell code)
        $url = stripslashes($url);

        // Check status-code of URL (or valid local file)
        $tempFile = (preg_match("/^(?:https?):\/\//i", $url)) ? false : true;
        if ($tempFile) $validUrl = $this->validateTempFile($url);
        else $validUrl = $this->validateUrl($url);
        if ($validUrl !== true) return false;

        try {
            $content = file_get_contents($url);
            if ($content === false) {
                $this->setMessages("Unable to read image");
                $this->setErrorData("Unknown image-url");
                return false;
            } else {
                return $content;
            }
        } catch (\Throwable $e) {
            $this->setMessages("Unable to read image");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Resize image by content
     *
     * @param string $content
     * @param int $maxHeight
     * @param int $maxWidth
     * @param int|null $thumbMaxHeight
     * @param int|null $thumbMaxWidth
     * @return array|boolean|string
     */
    public function resizeByContent($content, $maxHeight, $maxWidth, $thumbMaxHeight = null, $thumbMaxWidth = null)
    {
        return $this->resize(null, $content, $maxHeight, $maxWidth, $thumbMaxHeight, $thumbMaxWidth);
    }

    /**
     * Resize image by file
     *
     * @param string $path
     * @param int $maxHeight
     * @param int $maxWidth
     * @param int|null $thumbMaxHeight
     * @param int|null $thumbMaxWidth
     * @return array|boolean|string
     */
    public function resizeByFile($path, $maxHeight, $maxWidth, $thumbMaxHeight = null, $thumbMaxWidth = null)
    {
        return $this->resize($path, null, $maxHeight, $maxWidth, $thumbMaxHeight, $thumbMaxWidth);
    }

    /**
     * Resize image by URL
     *
     * @param string $url
     * @param int $maxHeight
     * @param int $maxWidth
     * @param int|null $thumbMaxHeight
     * @param int|null $thumbMaxWidth
     * @return array|boolean|string
     */
    public function resizeByUrl($url, $maxHeight, $maxWidth, $thumbMaxHeight = null, $thumbMaxWidth = null)
    {
        // Get image-content
        $content = $this->readImageByUrl($url);
        if ($content === false) return false;

        // Resize image
        return $this->resize(null, $content, $maxHeight, $maxWidth, $thumbMaxHeight, $thumbMaxWidth);
    }

    /**
     * Validate URL
     *
     * @param string $url
     * @return boolean
     */
    public function validateUrl($url)
    {
        // Prevent error "Failed to enable crypto":
        // The issue is down to the server certificate being presented as a wildcard so it can allow all sub-domains under the same certificate,
        // but for some reason the wildcard is used literally during the SSL verify leading to failure
        // Solution: https://stackoverflow.com/questions/40830265/php-errors-with-get-headers-and-ssl
        stream_context_set_default([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $headers = get_headers($url);
        $statusCode = substr($headers[0], 9, 3);
        if ($statusCode != "200") {
            $this->setMessages("Invalid image-url (status-code: {$statusCode})");
            return false;
        } else {
            return true;
        }
    }

    /**
     * Optimize image
     *
     * @param string $imageString
     * @return boolean|string
     */
    private function optimize($imageString)
    {
        // Create temporary file
        $filename = tempnam(sys_get_temp_dir(), "image-optimizer");
        file_put_contents($filename, $imageString);

        // Optimize image
        try {
            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize($filename);
            $optimizedImageString = file_get_contents($filename);

            // Remove temporary file
            unlink($filename);

            // Return
            return $optimizedImageString;
        } catch (\Throwable $e) {
            // Remove temporary file
            unlink($filename);

            // Set errors
            $this->setMessages("Unable to optimize image");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Resize image
     *
     * @param string|null $path
     * @param string|null $content
     * @param int $maxHeight
     * @param int $maxWidth
     * @param int|null $thumbMaxHeight
     * @param int|null $thumbMaxWidth
     * @return array|boolean|string
     */
    private function resize($path = null, $content = null, $maxHeight, $maxWidth, $thumbMaxHeight = null, $thumbMaxWidth = null)
    {
        if (empty($path) && empty($content)) {
            $this->setMessages("No image provided");
            return false;
        }

        // Set memory-size to avoid OOM
        ini_set('memory_limit', '1024M');

        try {
            // Initialize image
            if (!empty($path)) $image = new ImageResize($path);
            else $image = ImageResize::createFromString($content);

            // Resize image
            $image->resizeToBestFit($maxWidth, $maxHeight);
            $resizedImageString = $image->getImageAsString();

            // Optimize image
            $optimizedImageString = $this->optimize($resizedImageString);

            // Resize thumbnail (if requested)
            $thumb = (!empty($thumbMaxHeight) && !empty($thumbMaxWidth)) ? true : false;
            if ($thumb === true) {
                $thumbnail = ImageResize::createFromString($content);
                $thumbnail->resizeToBestFit($thumbMaxWidth, $thumbMaxHeight);
                $resizedThumbString = $thumbnail->getImageAsString();

                // Optimize thumbnail (if requested)
                $optimizedThumbString = $this->optimize($resizedThumbString);
            }

            // Return
            return ($thumb === true) ? ["image"=>$optimizedImageString, "thumbnail"=>$optimizedThumbString] : $optimizedImageString;
        } catch (\Throwable $e) {
            $this->setMessages("Unable to resize image");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Validate local file
     *
     * @param string $path
     * @return boolean
     */
    private function validateTempFile($path)
    {
        $tmpDir = sys_get_temp_dir();
        if (substr($path, 0, (strlen($tmpDir) + 1)) != $tmpDir . "/") {
            $this->setMessages("Invalid local file ($path)");
            return false;
        }

        $valid = is_file($path) && is_readable($path);
        if ($valid === false) {
            $this->setMessages("Invalid local file ($path)");
            return false;
        } else {
            return true;
        }
    }
}
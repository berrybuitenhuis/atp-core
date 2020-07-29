<?php

namespace AtpCore\File;

use AtpCore\BaseClass;
use \Gumlet\ImageResize;
use \Spatie\ImageOptimizer\OptimizerChainFactory;

class Image extends BaseClass
{
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
        // Check status-code of URL
        $validUrl = $this->validateUrl($url);
        if ($validUrl !== true) return false;
        
        try {
            $content = file_get_contents($url);
            if ($content === false) {
                $this->setMessages("Unable to resize image");
                $this->setErrorData("Unknown image-url");
                return false;
            } else {
                return $this->resize(null, $content, $maxHeight, $maxWidth, $thumbMaxHeight, $thumbMaxWidth);
            }
        } catch (\Throwable $e) {
            $this->setMessages("Unable to resize image");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Validate URL
     *
     * @param string $url
     * @return boolean
     */
    public function validateUrl($url)
    {
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
        ini_set('memory_limit', '500M');
        
        try {
            // Initialize image
            if (!empty($path)) $image = new ImageResize($path);
            else $image = ImageResize::createFromString($content);
            $thumb = (!empty($thumbMaxHeight) && !empty($thumbMaxWidth)) ? true : false;

            // Resize image
            $image->resizeToBestFit($maxWidth, $maxHeight);
            $resizedImageString = $image->getImageAsString();

            // Resize thumbnail (if requested)
            if ($thumb === true) {
                if (!empty($path)) $thumbnail = new ImageResize($path);
                else $thumbnail = ImageResize::createFromString($content);
                $thumbnail->resizeToBestFit($thumbMaxWidth, $thumbMaxHeight);
                $resizedThumbString = $thumbnail->getImageAsString();
            }

            // Optimize image
            $optimizedImageString = $this->optimize($resizedImageString);

            // Optimize thumbnail (if requested)
            if ($thumb === true) {
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

}
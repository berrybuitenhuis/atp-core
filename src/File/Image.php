<?php

namespace AtpCore\File;

use \Gumlet\ImageResize;
use \Spatie\ImageOptimizer\OptimizerChainFactory;

class Image
{
    public $errorData = [];
    public $messages = [];

    /**
     * Resize image by content
     *
     * @param string $content
     * @param int $maxHeight
     * @param int $maxWidth
     * @return string|boolean
     */
    public function resizeByContent($content, $maxHeight, $maxWidth)
    {
        try {
            // Resize image
            $image = ImageResize::createFromString($content);
            $image->resizeToBestFit($maxWidth, $maxHeight);
            $resizedImageString = $image->getImageAsString();

            // Optimized image
            return $this->optimize($resizedImageString);
        } catch (\Throwable $e) {
            $this->messages[] = "Unable to resize image";
            $this->errorData[] = $e->getMessage();
            return false;
        }
    }

    /**
     * Resize image by file
     *
     * @param string $imagePath
     * @param int $maxHeight
     * @param int $maxWidth
     * @return string|boolean
     */
    public function resizeByFile($imagePath, $maxHeight, $maxWidth)
    {
        try {
            // Resize image
            $image = new ImageResize($imagePath);
            $image->resizeToBestFit($maxWidth, $maxHeight);
            $resizedImageString = $image->getImageAsString();

            // Optimized image
            return $this->optimize($resizedImageString);
        } catch (\Throwable $e) {
            $this->messages[] = "Unable to resize image";
            $this->errorData[] = $e->getMessage();
            return false;
        }
    }

    /**
     * Resize image by URL
     *
     * @param string $imageUrl
     * @param int $maxHeight
     * @param int $maxWidth
     * @return string|boolean
     */
    public function resizeByUrl($imageUrl, $maxHeight, $maxWidth)
    {
        // Check status-code of URL
        $validUrl = $this->validateUrl($imageUrl);
        if ($validUrl !== true) return false;
        
        try {
            $content = file_get_contents($imageUrl);
            if ($content === false) {
                $this->messages[] = "Unable to resize image";
                $this->errorData[] = "Unknown image-url";
                return false;
            } else {
                return $this->resizeByContent($content, $maxHeight, $maxWidth);
            }
        } catch (\Throwable $e) {
            $this->messages[] = "Unable to resize image";
            $this->errorData[] = $e->getMessage();
            return false;
        }
    }

    public function validateUrl($imageUrl)
    {
        $headers = get_headers($imageUrl);
        $statusCode = substr($headers[0], 9, 3);
        if ($statusCode != "200") {
            $this->messages[] = "Invalid image-url (status-code: {$statusCode})";
            return false;
        } else {
            return true;
        }
    }

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
            $this->messages[] = "Unable to optimize image";
            $this->errorData[] = $e->getMessage();
            return false;
        }
    }
}
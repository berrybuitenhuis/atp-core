<?php

namespace AtpCore\File;

use \Gumlet\ImageResize;

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
     * @return string
     */
    public function resizeByContent($content, $maxHeight, $maxWidth)
    {
        try {
            $image = ImageResize::createFromString($content);
            $image->resizeToBestFit($maxWidth, $maxHeight);
            return $image->getImageAsString();
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
     * @return string
     */
    public function resizeByFile($imagePath, $maxHeight, $maxWidth)
    {
        try {
            $image = new ImageResize($imagePath);
            $image->resizeToBestFit($maxWidth, $maxHeight);
            return $image->getImageAsString();
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
     * @return string
     */
    public function resizeByUrl($imageUrl, $maxHeight, $maxWidth)
    {
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

}
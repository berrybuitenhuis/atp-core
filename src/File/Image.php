<?php

namespace AtpCore\File;

use \Gumlet\ImageResize;

class Image
{

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
        $image = ImageResize::createFromString($content);
        $image->resizeToBestFit($maxWidth, $maxHeight);
        return $image->getImageAsString();
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
        $image = new ImageResize($imagePath);
        $image->resizeToBestFit($maxWidth, $maxHeight);
        return $image->getImageAsString();
    }

}
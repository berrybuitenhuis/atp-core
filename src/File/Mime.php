<?php

namespace AtpCore\File;

use AtpCore\Format;

class Mime
{

    /**
     * Validate file-extension by mime-type
     *
     * @param string $mimeType
     * @param string $fileExtension
     * @return bool
     */
    public static function validateFileExtension($mimeType, $fileExtension)
    {
        $extensions = self::getFileExtensions($mimeType);
        return in_array($fileExtension, $extensions);
    }

    /**
     * Get file-extenions by mime-type
     *
     * @param string $mimeType
     * @return array
     */
    private static function getFileExtensions($mimeType)
    {
        switch(Format::lowercase($mimeType)) {
            case "application/json":
                $extensions = ["json"];
                break;
            case "application/msword":
                $extensions = ["doc"];
                break;
            case "application/vnd.ms-excel":
                $extensions = ["xls"];
                break;
            case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
                $extensions = ["xlsx"];
                break;
            case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
                $extensions = ["docx"];
                break;
            case "audio/mpeg":
                $extensions = ["mp2","mp3","mpga"];
                break;
            case "image/bmp":
                $extensions = ["bmp"];
                break;
            case "image/gif":
                $extensions = ["gif"];
                break;
            case "image/jpeg":
                $extensions = ["jpe","jpeg","jpg"];
                break;
            case "image/png":
                $extensions = ["png"];
                break;
            case "image/svg+xml":
                $extensions = ["svg","svgz"];
                break;
            case "image/tiff":
                $extensions = ["tif","tiff"];
                break;
            case "text/plain":
                $extensions = ["txt"];
                break;
            case "video/mp4":
                $extensions = ["mp4","mpg4"];
                break;
            case "video/mpeg":
                $extensions = ["mpeg","mpg"];
                break;
            case "video/x-msvideo":
                $extensions = ["avi"];
                break;
        }

        // Return
        return $extensions;
    }
}
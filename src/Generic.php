<?php

namespace AtpCore;

class Generic
{

    /**
     * Set file/directory permissions recursively within a source-path
     *
     * @param string $path
     * @param int $mode
     * @return void
     */
    public static function chmodRecursive($path, $mode)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $file) {
            @chmod($file->getPathname(), $mode);
        }
    }

    public static function emptyDirectory($path, $recursive = false)
    {
        // Remove trailing slash from path
        $path = rtrim($path, '/');

        // Iterate files in path
        $files = glob("$path/*");
        foreach ($files as $file) {
            if (is_dir($file)) {
                $dirFiles = glob("$path/$file/*");
                if (count($dirFiles) > 0 && $recursive === true) {
                    self::emptyDirectory("$path/$file", true);
                } else {
                    rmdir($file);
                }
            } elseif (is_file($file)) {
                unlink($file);
            }
        }
    }
}

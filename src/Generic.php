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

}

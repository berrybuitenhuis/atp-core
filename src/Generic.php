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
                $dirFiles = glob("$file/*");
                if (count($dirFiles) > 0 && $recursive === true) {
                    self::emptyDirectory($file, true);
                } else {
                    rmdir($file);
                }
            } elseif (is_file($file)) {
                unlink($file);
            }
        }
    }

    public static function getLocalFiles($path)
    {
        // Return if path not exists
        if (!is_dir($path)) {
            return null;
        }

        // Get all files (also hidden-files)
        $files = glob($path . '/{*,.*}', GLOB_BRACE | GLOB_NOSORT);
        // Filter "." and ".." from file-list
        $files = array_diff($files, [$path . '/.', $path . '/..']);
        if (empty($files)) $files = null;

        // Return
        return $files;
    }

    public static function isEmptyDirectory($path, $failIfNotExists = false)
    {
        if ($failIfNotExists === true && !is_dir($path)) {
            return false;
        }

        // Return
        return self::getLocalFiles($path) === null;
    }
}

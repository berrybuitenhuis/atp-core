<?php

namespace AtpCore\File;

class SFTP
{
    private $client;

    public function __construct($host, $port, $username, $password) {
        $this->client = new \phpseclib3\Net\SFTP($host, $port);
        $this->client->login($username, $password);
    }

    public function getFiles($directory) {
        // Get files from directory
        $files = $this->client->nlist($directory);

        // Iterate files
        $list = [];
        foreach ($files as $file) {
            // Skip directories
            if ($this->client->is_dir($file) == "dir") continue;

            // Add file to list
            $modTime = '';
            $list[] = [
                "file" =>$file,
                "created" => $modTime,
                "filesize" => sprintf("%.2f", $this->client->size($file)),
            ];
        }

        // Return
        return $list;
    }
}
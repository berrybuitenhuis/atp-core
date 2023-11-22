<?php

namespace AtpCore\File;

use AtpCore\BaseClass;

class SFTP extends BaseClass
{
    private $host;
    private $password;
    private $port;
    private $username;

    public function __construct($host, $port, $username, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Get file-content of remote file
     *
     * @param string $directory
     * @param string $remoteFileName
     * @return string|false
     */
    public function getFileContent($directory, $remoteFileName) {
        // Setup connection
        $client = $this->connect();
        if ($client === false) return false;

        // Get file-contents from remote-file
        $contents = $client->get("$directory/$remoteFileName");
        if ($contents === false) {
            $this->setMessages("Could not open remote file: $directory/$remoteFileName");
        }

        // Close connection
        $this->disconnect($client);

        // Return
        return $contents;
    }

    /**
     * Get list of files in remote directory
     *
     * @param string $directory
     * @param string $orderBy
     * @param string $orderDirection
     * @return array|false
     */
    public function getFiles($directory, $orderBy = "mtime", $orderDirection = SORT_DESC) {
        // Setup connection
        $client = $this->connect();
        if ($client === false) return false;

        // Get files from directory
        $client->setListOrder($orderBy, $orderDirection);
        $files = $client->nlist($directory);

        // Iterate files
        $list = [];
        foreach ($files as $file) {
            // Skip directories
            if ($client->is_dir($file) == "dir") continue;

            // Add file to list
            $list[] = [
                "file" =>$file,
                "created" => (new \DateTime())->setTimestamp($client->filemtime($directory ."/" . $file)),
                "size" => sprintf("%.2f", $client->filesize($directory ."/" . $file)),
            ];
        }

        // Close connection
        $this->disconnect($client);

        // Return
        return $list;
    }

    /**
     * Setup SFTP-connection
     *
     * @return \phpseclib3\Net\SFTP|false
     */
    private function connect()
    {
        // Initialize connection
        try {
            $client = new \phpseclib3\Net\SFTP($this->host, $this->port);
            $client->login($this->username, $this->password);
        } catch (\Exception $exception) {
            $this->setMessages("Failed to connect to $this->host:$this->port");
            $this->setErrorData($exception->getMessage());
            return false;
        }

        // Check connection established
        if ($client->isConnected() === false) {
            $this->setMessages("Failed to connect to $this->host:$this->port");
            $this->disconnect($client);
            return false;
        }

        // Check connection is authenticated
        if ($client->isAuthenticated() === false) {
            $this->setMessages("Failed to authenticate to $this->host");
            $this->disconnect($client);
            return false;
        }

        // Return
        return $client;
    }

    /**
     * Close SFTP-client
     *
     * @param \phpseclib3\Net\SFTP $client
     */
    private function disconnect($client)
    {
        $client->disconnect();
    }
}
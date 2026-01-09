<?php

namespace AtpCore\File;

use AtpCore\Error;

class FTP
{
    private $host;
    private $password;
    private $username;

    public function __construct($host, $username, $password) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Download file from FTP
     *
     * @param string $sourceFile
     * @param string $targetFile
     * @return true|Error
     */
    public function download($sourceFile, $targetFile)
    {
        // Initialize connection
        $connection = $this->connect();
        if (Error::isError($connection)) {
            return $connection;
        }

        $res = ftp_get($connection, $targetFile, $sourceFile, FTP_BINARY);
        if ($res === false) {
            $res = new Error(messages: ["Failed to download $sourceFile"]);
        }

        // Close connection
        $this->disconnect($connection);

        // Return
        return $res;
    }

    /**
     * Get list of files in remote directory
     *
     * @param string $directory
     * @return array|Error
     */
    public function getFiles($directory)
    {
        // Initialize connection
        $connection = $this->connect();
        if (Error::isError($connection)) {
            return $connection;
        }

        // Iterate files
        $files = ftp_rawlist($connection, $directory);
        $list = [];
        foreach ($files as $file) {
            // Get file-details
            preg_match('/(\S+)\s+(\d+)\s+(\S+)\s+(\S+)\s+(\d+)\s+(\w{3}\s+\d{2})\s+(\d{4}|\d{2}:\d{2})\s+(.+)/', $file, $fileDetails);

            // Add file to list
            $list[] = [
                "file" => $fileDetails[8],
                "created" => $this->getCreated($fileDetails[6], $fileDetails[7]),
                "size" => $fileDetails[5],
            ];
        }

        // Close connection
        $this->disconnect($connection);

        // Return
        return $list;
    }

    /**
     * Setup FTP-connection
     *
     * @return \FTP\Connection|Error
     */
    private function connect()
    {
        // Initialize connection
        $connection = ftp_connect($this->host);
        if ($connection === false) {
            return new Error(messages: ["Failed to connect to $this->host:21"]);
        }
        $login = ftp_login($connection, $this->username, $this->password);
        if ($login === false) {
            return new Error(messages: ["Authentication failed connecting $this->host:21"]);
        }
        ftp_pasv($connection, true);

        // Return
        return $connection;
    }

    /**
     * Close FTP-client
     *
     * @param \FTP\Connection $connection
     */
    private function disconnect($connection)
    {
        ftp_close($connection);
    }

    /**
     * Get proper file-date
     *
     * @param string $fileDate
     * @param string|int $fileYear
     * @return \DateTime
     */
    private function getCreated($fileDate, $fileYear)
    {
        $fileYear = (strstr($fileYear, ":")) ? date("Y") : $fileYear;
        $date = (new \DateTime())->setTimestamp(strtotime("$fileDate $fileYear"));
        if ($date->format("Y-m-d") > (new \AtpCore\Date())->format("Y-m-d")) {
            return $this->getCreated($fileDate, $fileYear - 1);
        }

        // Return
        return $date;
    }
}
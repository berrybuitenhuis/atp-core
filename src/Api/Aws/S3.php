<?php
/**
 * API-information: https://docs.aws.amazon.com/aws-sdk-php/v3/api/
 */
namespace AtpCore\Api\Aws;

use AtpCore\BaseClass;
use Aws\S3\S3Client;
use Throwable;

class S3 extends BaseClass
{

    private $client;
    private $config;

    /**
     * Constructor
     *
     * @param string $version
     * @param string $region
     * @param string $awsKey
     * @param string $awsSecret
     */
    public function __construct($version = "latest", $region = "eu-west-1", $awsKey = null, $awsSecret = null)
    {
        // Set config
        $this->config = [
            'version' => $version,
            'region' => $region,
        ];
        if (!empty($awsKey)) {
            $this->config['credentials'] = [
                'key' => $awsKey,
                'secret' => $awsSecret,
            ];
        }

        // Set client
        $this->client = new S3Client($this->config);

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Delete file from AWS S3-bucket
     *
     * @param string $bucket
     * @param string $filename
     * @return bool
     */
    public function delete($bucket, $filename)
    {
        // Check object exists
        $exists = $this->client->doesObjectExist($bucket, $filename);
        if ($exists !== true) {
            return true;
        }

        // Delete file
        try {
            $this->client->deleteObject(["Bucket"=>$bucket, "Key"=>$filename]);
            return true;
        } catch(Throwable $e) {
            $this->setMessages("Delete failed");
            $this->setErrorData($e->getMessage());
            return false;
        }

    }

    /**
     * Upload file to AWS S3-bucket
     *
     * @param string $bucket
     * @param string $file
     * @param string|null $filename
     * @param string $acl
     * @param boolean $overwrite
     * @return \Aws\Result|bool
     */
    public function upload($bucket, $file, $filename = null, $acl = 'private', $overwrite = false)
    {
        // Check if file already exists (if overwrite disabled)
        if ($overwrite !== true && !empty($filename)) {
            $exists = $this->client->doesObjectExist($bucket, $filename);
            if ($exists === true) {
                $this->setMessages("File already exists (no overwrite allowed)");
                return false;
            }
        }

        // Check if file exists (readable)
        if (!is_file($file) && !stristr($file, "http://") && !stristr($file, "https://")) {
            $this->setMessages("File not found ({$file})");
            return false;
        } elseif (ini_get('allow_url_fopen') == false && (stristr($file, "http://") || stristr($file, "https://"))) {
            $this->setMessages("URL upload not allowed ({$file})");
            return false;
        }

        // Get content
        $content = fopen($file, 'r');

        // Upload file
        return $this->save($bucket, $content, $filename, $acl, $overwrite);
    }

    /**
     * Save file into AWS S3-bucket
     *
     * @param string $bucket
     * @param string $content
     * @param string|null $filename
     * @param string $acl
     * @param boolean $overwrite
     * @param boolean $skipIfExists
     * @return \Aws\Result|bool
     */
    public function save($bucket, $content, $filename = null, $acl = 'private', $overwrite = false, $failIfExists = false)
    {
        // Check content
        if (empty($content)) {
            $this->setMessages("No content available");
            return false;
        }

        // Get filename if not provided
        if (empty($filename)) {
            $filename = md5($content);
        }

        // Check if file already exists (if overwrite disabled)
        if ($overwrite !== true) {
            $exists = $this->client->doesObjectExist($bucket, $filename);
            if ($exists === true && $failIfExists !== true) {
                return $this->client->headObject(['Bucket'=>$bucket, 'Key'=>$filename]);
            } elseif ($exists === true && $failIfExists === true) {
                $this->setMessages("File already exists (no overwrite allowed)");
                return false;
            }
        }

        // Upload file to S3-bucket
        try {
            return $this->client->upload($bucket, $filename, $content, $acl);
        } catch(Throwable $e) {
            $this->setMessages("Upload failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }
}
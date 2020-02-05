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
     * Upload file to AWS S3-bucket

     * @param string $bucket
     * @param string $filename
     * @param string $file
     * @param string $acl
     * @return \Aws\Result|bool
     */
    public function upload($bucket, $filename, $file, $acl = 'private')
    {
        // Check if file exists (readable)
        if (!is_file($file)) {
            $this->setErrorData("File not found ({$file})");
            return false;
        }

        try {
            return $this->client->upload(
                $bucket,
                $filename,
                fopen($file, 'r'),
                $acl
            );
        } catch(Throwable $e) {
            $this->setErrorData($e->getMessage());
            return false;
        }
    }
}
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
     * Copy file to another S3-bucket
     *
     * @param string $sourceBucket
     * @param string $sourceFilename
     * @param string $targetBucket
     * @param string $targetFilename
     * @param boolean $overwrite
     * @param boolean $failIfExists
     * @return \AWS\Result|boolean
     */
    public function copy($sourceBucket, $sourceFilename, $targetBucket, $targetFilename, $overwrite = false, $failIfExists = false)
    {
        // Check if source-file exists
        $exists = $this->client->doesObjectExist($sourceBucket, $sourceFilename);
        if ($exists !== true) {
            $this->setMessages("File doesn't exist");
            return false;
        }

        // Check if target-file already exists (if overwrite disabled)
        if ($overwrite !== true) {
            $exists = $this->client->doesObjectExist($targetBucket, $targetFilename);
            if ($exists === true && $failIfExists !== true) {
                return $this->client->headObject(['Bucket'=>$targetBucket, 'Key'=>$targetFilename]);
            } elseif ($exists === true && $failIfExists === true) {
                $this->setMessages("File already exists (no overwrite allowed)");
                return false;
            }
        }

        try {
            // Copy file to S3-bucket
            $result = $this->client->copyObject(['Bucket'=>$targetBucket, 'Key'=>$targetFilename, 'CopySource'=>"{$sourceBucket}/{$sourceFilename}"]);

            // Check if target-file exists
            $exists = $this->client->doesObjectExist($targetBucket, $targetFilename);
            if ($exists !== true) {
                $this->setMessages("File (target) not copied");
                return false;
            }

            // Return
            return $result;
        } catch(Throwable $e) {
            $this->setMessages("Copying file failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
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
     * Get tags of object
     *
     * @param string $bucket
     * @param string $filename
     * @return array|bool
     */
    public function getFileTags($bucket, $filename)
    {
        // Check if object exists
        $exists = $this->client->doesObjectExist($bucket, $filename);
        if ($exists !== true) {
            $this->setMessages("File doesn't exist");
            return false;
        }

        // Get current file-tags
        try {
            $tags = [];
            $tagSet = $this->client->getObjectTagging(["Bucket" => $bucket, "Key" => $filename])->toArray()['TagSet'];
            if (!empty($tagSet)) {
                // Convert tag-set into key-value array
                foreach ($tagSet AS $tag) {
                    $tags[$tag['Key']] = $tag['Value'];
                }
            }

            // Returns
            return $tags;
        } catch(Throwable $e) {
            $this->setMessages("Getting file-tags failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Add/modify tag on existing object
     *
     * @param string $bucket
     * @param string $filename
     * @param array $tags (as key-value pairs)
     * @return boolean
     */
    public function modifyFileTags($bucket, $filename, $tags)
    {
        // Get current file-tags
        $tagArray = $this->getFileTags($bucket, $filename);
        if ($tagArray === false) return false;

        // Add/modify tags in tag-array
        $tagArray = array_merge($tagArray, $tags);

        // Convert key-value array into tag-set
        $tagSet = [];
        foreach ($tagArray AS $tagKey => $tagValue) {
            $tagSet[] = ['Key'=>$tagKey, 'Value'=>$tagValue];
        }

        // Update file-tags
        try {
            $this->client->putObjectTagging([
                'Bucket' => $bucket,
                'Key' => $filename,
                'Tagging' => [
                    'TagSet' => $tagSet,
                ],
            ]);

            // Return
            return true;
        } catch(Throwable $e) {
            $this->setMessages("Modifying file-tags failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Move file to another S3-bucket
     *
     * @param string $sourceBucket
     * @param string $sourceFilename
     * @param string $targetBucket
     * @param string $targetFilename
     * @param boolean $overwrite
     * @param boolean $failIfExists
     * @return \AWS\Result|boolean
     */
    public function move($sourceBucket, $sourceFilename, $targetBucket, $targetFilename, $overwrite = false, $failIfExists = false)
    {
        $result = $this->copy($sourceBucket, $sourceFilename, $targetBucket, $targetFilename, $overwrite, $failIfExists);
        if ($result === false) return false;

        try {
            // Delete source-file
            $res = $this->delete($sourceBucket, $sourceFilename);
            if ($res !== true) {
                $this->setMessages("File (source) not deleted");
                return false;
            }

            // Return
            return $result;
        } catch(Throwable $e) {
            $this->setMessages("Moving file failed");
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
     * @param array $tags
     * @param boolean $overwrite
     * @return \Aws\Result|bool
     */
    public function upload($bucket, $file, $filename = null, $acl = 'private', $tags = null, $overwrite = false)
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
        return $this->save($bucket, $content, $filename, $acl, $tags, $overwrite);
    }

    /**
     * Save file into AWS S3-bucket
     *
     * @param string $bucket
     * @param string $content
     * @param string|null $filename
     * @param string $acl
     * @param array $tags
     * @param boolean $overwrite
     * @param boolean $skipIfExists
     * @return \Aws\Result|bool
     */
    public function save($bucket, $content, $filename = null, $acl = 'private', $tags = null, $overwrite = false, $failIfExists = false)
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

        // Set tag-string
        $options = [];
        if (!empty($tags)) {
            $tagging = "";
            foreach ($tags AS $tagKey => $tagValue) {
                if (!empty($tagging)) $tagging .= "&";
                $tagging .= "{$tagKey}={$tagValue}";
            }
            $options['params']["Tagging"] = $tagging;
        }

        // Upload file to S3-bucket
        try {
            return $this->client->upload($bucket, $filename, $content, $acl, $options);
        } catch(Throwable $e) {
            $this->setMessages("Upload failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }
}
<?php
/**
 * API-information:
 * https://docs.getunleash.io/sdks/php_sdk
 * https://github.com/Unleash/unleash-client-php
 */
namespace AtpCore\Api\Unleash;

use AtpCore\BaseClass;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Unleash\Client\Unleash;
use Unleash\Client\UnleashBuilder;
use Unleash\Client\Configuration\UnleashContext;

class Api extends BaseClass
{
    private Unleash $client;
    private bool $clientInitialized;
    private UnleashContext $context;

    /**
     * Constructor
     *
     * @param string $appUrl
     * @param string $instanceId
     * @param string $environment
     * @param boolean $debug
     */
    public function __construct($appUrl, $instanceId, $environment, $cacheTTL = 600, $debug = false)
    {
        // Reset error-messages
        $this->resetErrors();

        // Set client
        try {
            $this->client = UnleashBuilder::createForGitlab()
                ->withInstanceId($instanceId)
                ->withAppUrl($appUrl)
                ->withGitlabEnvironment($environment)
                ->withCacheHandler(
                    new FilesystemCachePool(
                        new Filesystem(
                            new Local(sys_get_temp_dir()),
                        ),
                    )
                )
                ->withCacheTimeToLive($cacheTTL)
                ->build();

            $this->clientInitialized = true;
        } catch (\Exception $e) {
            $this->setErrorData($e->getTrace());
            $this->setMessages($e->getMessage());
            $this->clientInitialized = false;
        }
    }

    public function setContext($userId, $ipAddress, $sessionId)
    {
        $this->context = new UnleashContext($userId, $ipAddress, $sessionId);
    }

    public function checkFeature($featureName, $defaulValue = false)
    {
        if ($this->clientInitialized === false) return $defaulValue;
        return $this->client->isEnabled($featureName, $this->context, $defaulValue);
    }
}
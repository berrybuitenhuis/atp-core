<?php

namespace AtpCore\Zf\Factory;

use OAuth2\Server as OAuth2Server;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class OptionsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');
        $storage = [];
        foreach ($config['zf-oauth2']['storage'] as $storageKey => $storagesService) {
            $storage[$storageKey] = $container->get($storagesService);
        }
        $oAuthServer = new OAuth2Server($storage);

        $options = new $requestedName(
            $oAuthServer
        );

        return $options;
    }

}
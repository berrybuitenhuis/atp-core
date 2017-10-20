<?php

namespace AtpCore\Zf\Factory;

use OAuth2\Server as OAuth2Server;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class OptionsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $options = new $requestedName();

        return $options;
    }

}
<?php

namespace AtpCore\Zf\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class OptionsFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return array|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $options = new $requestedName();

        return $options;
    }

}
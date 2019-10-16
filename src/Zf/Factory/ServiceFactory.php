<?php

namespace AtpCore\Zf\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // Set repository-name (replace service into repository
        $repositoryName = str_replace("\Service\\", "\Repository\\", $requestedName);
        $repositoryName = preg_replace("~Service(?!.*Service)~", "Repository", $repositoryName);

        /** @var $repository */
        $repository = $container->get($repositoryName);

        /** @var $service */
        $service = new $requestedName(
            $repository
        );

        return $service;
    }

}
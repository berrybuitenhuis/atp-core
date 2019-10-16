<?php

namespace AtpCore\Zf\Factory;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class RepositoryFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $objectManager = $container->get(EntityManager::class);

        $config = $container->get('Config');

        // Set options-name (replace repository)
        $optionsName = str_replace("\Repository\\", "\Options\\", $requestedName);
        $optionsName = preg_replace("~Repository(?!.*Repository)~", "", $optionsName);
        $options = (class_exists($optionsName)) ? $container->get($optionsName): null;

        /** @var $repository */
        $repository = new $requestedName(
            $objectManager,
            $config,
            $options
        );

        // Return
        return $repository;
    }

}
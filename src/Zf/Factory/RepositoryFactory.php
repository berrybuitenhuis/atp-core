<?php

namespace AtpCore\Zf\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class RepositoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var $objectManager \Doctrine\ORM\EntityManager */
        $objectManager = $container->get(\Doctrine\ORM\EntityManager::class);

        /** @var $config \Zend\Config\Config */
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
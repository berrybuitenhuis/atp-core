<?php
namespace AtpCore\Laminas\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * AnsiQuoteStrategy that prefixes the mapped schema (e.g. "rdw.opendata_vehicle_anomaly"),
 * so queries don't depend on the dbname of the connection
 */
class SchemaQuoteStrategy extends AnsiQuoteStrategy
{
    public function getTableName(ClassMetadata $class, AbstractPlatform $platform)
    {
        $schema = $class->getSchemaName();
        return (empty($schema)) ? $class->getTableName() : "$schema.{$class->getTableName()}";
    }
}

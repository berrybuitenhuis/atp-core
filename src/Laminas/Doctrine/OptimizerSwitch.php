<?php

namespace AtpCore\Laminas\Doctrine;

use Doctrine\DBAL\Connection;

/**
 * Helper for toggling MySQL "optimizer_switch" flags on a Doctrine DBAL connection.
 *
 * MySQL merges a partial "SET SESSION optimizer_switch = 'flag=off'" into the existing value, so only the
 * given flag is changed; all other flags keep their current state.
 */
class OptimizerSwitch
{
    /**
     * Enable a single optimizer_switch flag for the current session.
     *
     * @param Connection $connection
     * @param string $flag e.g. "prefer_ordering_index"
     * @return void
     */
    public static function enable(Connection $connection, $flag)
    {
        self::set($connection, $flag, true);
    }

    /**
     * Disable a single optimizer_switch flag for the current session.
     *
     * @param Connection $connection
     * @param string $flag e.g. "prefer_ordering_index"
     * @return void
     */
    public static function disable(Connection $connection, $flag)
    {
        self::set($connection, $flag, false);
    }

    /**
     * Enable or disable a single optimizer_switch flag for the current session.
     *
     * @param Connection $connection
     * @param string $flag e.g. "prefer_ordering_index"
     * @param bool $enabled
     * @return void
     */
    private static function set(Connection $connection, $flag, $enabled)
    {
        $value = $flag . "=" . ($enabled ? "on" : "off");
        $connection->executeStatement("SET SESSION optimizer_switch = " . $connection->quote($value));
    }
}
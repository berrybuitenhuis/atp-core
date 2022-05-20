<?php

namespace AtpCore\Laminas\Session\Config;

use Laminas\Session\Config;

/**
 * Session ManagerInterface implementation utilizing ext/session
 */
class SessionConfig extends Config\SessionConfig
{

    /**
     * Set storage option in backend configuration store
     *
     * @param  string $storageName
     * @param  mixed $storageValue
     * @return Config\SessionConfig
     * @throws \Exception\InvalidArgumentException
     */
    public function setStorageOption($storageName, $storageValue)
    {
        switch ($storageName) {
            case 'remember_me_seconds':
                // do nothing; not an INI option
                return;
            case 'url_rewriter_tags':
                $key = 'url_rewriter.tags';
                break;
            case 'save_handler':
                // Save handlers must be treated differently due to changes
                // introduced in PHP 7.2. Do not alter running INI setting.
                return $this;
            default:
                $key = 'session.' . $storageName;
                break;
        }

        $iniGet       = ini_get($key);
        $storageValue = (string) $storageValue;
        if (false !== $iniGet && (string) $iniGet === $storageValue) {
            return $this;
        }

        $sessionRequiresRestart = false;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            $sessionRequiresRestart = true;
        }

        // Dont set for php-unit run (prevent error: PHPUnit\Framework\Error\Warning: ini_set(): Headers already sent. You cannot change the session module's ini settings at this time)
        if (isset($_SERVER['SCRIPT_NAME'])
            && stristr($_SERVER['SCRIPT_NAME'], "phpunit")
            && !in_array($key, ["session.cookie_lifetime", "session.gc_maxlifetime"])) {
            $result = ini_set($key, $storageValue);
        }

        if ($sessionRequiresRestart) {
            session_start();
        }

        if (false === $result) {
            throw new \Exception\InvalidArgumentException(
                "'{$key}' is not a valid sessions-related ini setting."
            );
        }
        return $this;
    }

}
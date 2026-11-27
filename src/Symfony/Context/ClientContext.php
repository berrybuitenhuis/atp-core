<?php

namespace AtpCore\Symfony\Context;

/**
 * Who is asking; implemented by the application (e.g. its session), with its own client-enum
 */
interface ClientContext
{
    public function getClient(): \UnitEnum;

    /**
     * Raw client-identifier (for error-messages)
     */
    public function getClientIdentifier(): ?string;
}

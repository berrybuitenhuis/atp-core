<?php

namespace AtpCore\Symfony\Resource;

/**
 * Which rows a client sees, returned per client by Resource::scope()
 *
 *     return match ($client) {
 *         Client::AtpAdmin => Scope::unrestricted(),
 *         Client::AtpDashboard => Scope::where(fn (Where $w, Session $session) => $w->eq('Company', $session->getCompanyId())),
 *         default => Scope::denied(),
 *     };
 */
final readonly class Scope
{
    private function __construct(
        private bool $allowed,
        private ?\Closure $condition = null,
    ) {}

    /**
     * Allowed, only the rows matching the condition
     *
     * @param \Closure $condition fn (Where $w, <ClientContext of the application, e.g. Session> $c)
     */
    public static function where(\Closure $condition): self
    {
        return new self(true, $condition);
    }

    /**
     * Allowed, all rows
     */
    public static function unrestricted(): self
    {
        return new self(true);
    }

    /**
     * Not allowed (the client gets clientNotAllowed, 403)
     */
    public static function denied(): self
    {
        return new self(false);
    }

    public function getAllowed(): bool
    {
        return $this->allowed;
    }

    public function getCondition(): ?\Closure
    {
        return $this->condition;
    }
}

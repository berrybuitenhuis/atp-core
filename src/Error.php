<?php

namespace AtpCore;

class Error
{
    public function __construct(
        private mixed $data = null,
        private array $messages = [],
        private array $stackTrace = [],
    ) {}

    /**
     * Add error-message
     */
    public function addMessage(string $message, array $backtrace): void
    {
        $this->messages[] = $message;
        $this->stackTrace[] = [
            "file" => $backtrace[0]['file'],
            "class" => $backtrace[0]['class'],
            "function" => $backtrace[0]['function'],
            "line" => $backtrace[0]['line'],
            "message" => $message,
        ];
    }

    /**
     * Get error-data
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Get error-messages
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Get stack trace of error(s)
     */
    public function getStackTrace(): array
    {
        return $this->stackTrace;
    }

    /**
     * Set error-data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    public static function isError($data): bool
    {
        return $data instanceof Error;
    }

    public static function isNotError($data): bool
    {
        return ($data instanceof Error) === false;
    }
}

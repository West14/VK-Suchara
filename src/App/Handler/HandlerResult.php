<?php

namespace App\Handler;

class HandlerResult
{
    public function __construct(
        protected bool $continue,
        protected ?string $response = null
    ) {}

    public function shouldBreak(): bool
    {
        return !$this->continue;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }
}
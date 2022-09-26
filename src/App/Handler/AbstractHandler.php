<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 07.08.2021
 * Time: 2:53
 * Made with <3 by West from Bubuni Team
 */

namespace App\Handler;

use App;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerInterface;

abstract class AbstractHandler
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    abstract public function handle(): HandlerResult;

    protected function app(): App
    {
        return $this->app;
    }

    protected function logger(): LoggerInterface
    {
        return $this->app()->logger();
    }

    protected function getInput(string $key): int|string|array
    {
        return $this->app()->getFromRequest($key);
    }

    protected function getMessage(): array
    {
        return $this->getInput('object')['message'];
    }

    #[Pure]
    protected function continue(): HandlerResult
    {
        return new HandlerResult(true);
    }

    #[Pure]
    protected function break(?string $response = null): HandlerResult
    {
        return new HandlerResult(false, $response);
    }
}
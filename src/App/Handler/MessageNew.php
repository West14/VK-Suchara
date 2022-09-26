<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 07.08.2021
 * Time: 11:22
 * Made with <3 by West from Bubuni Team
 */

namespace App\Handler;

use App\Command\AbstractCommand;
use Psr\Log\LogLevel;
use VK\Exceptions\VKApiException;

class MessageNew extends AbstractHandler
{
    public function handle(): HandlerResult
    {
        /** @var array $message */
        $message = $this->getInput('object')['message'];
        if ($message['peer_id'] < 2000000000)
        {
            return $this->continue();
        }

        try {
            if ($this->isCommand($message['text']))
            {
                $handler = $this->getCommandHandler($message);
                if ($handler?->canUse($message['from_id']))
                {
                    $handler->handle();
                }
            }

        }
        catch (VKApiException $e)
        {
            $this->logger()->log(LogLevel::ERROR, "An VKAPI Exception occured: {$e->getErrorCode()} {$e->getErrorMessage()}");
            $this->logger()->log(LogLevel::ERROR, $this->app()->formatException($e));
        }
        catch (\Exception $e)
        {
            $this->logger()->log(LogLevel::ERROR, $this->app()->formatException($e));
        }

        return $this->continue();
    }

    public function isCommand(string $text): bool
    {
        return isset($text[0]) && $text[0] == '/';
    }

    public function getCommandHandler(array $message): ?AbstractCommand
    {
        $params = str_getcsv($message['text'], " ");
        $commandName = substr(array_shift($params), 1);

        if (!$this->commandExists($commandName))
        {
            return null;
        }
        return new ($this->getCommandMap()[$commandName])($this->app(), $message, $params);
    }

    public function commandExists(string $commandName): bool
    {
        return in_array($commandName, array_keys($this->getCommandMap()));
    }

    public function getCommandMap(): array
    {
        return $this->app()->container()['commandMap'];
    }
}
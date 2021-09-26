<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 07.08.2021
 * Time: 11:22
 * Made with <3 by West from Bubuni Team
 */

namespace App\Handler;

use App;
use App\Command\AbstractCommand;
use PDO;
use Psr\Log\LogLevel;

class Command extends AbstractHandler
{
    protected string $commandName;
    protected array $params;

    public function __construct(App $app)
    {
        parent::__construct($app);

        /** @var array $message */
        $message = $this->getInput('object')['message'];

        $params = str_getcsv($message['text'], " ");
        $this->commandName = substr(array_shift($params), 1);
        $this->params = $params;

    }

    public function handle(): HandlerResult
    {
        /** @var array $message */
        $message = $this->getInput('object')['message'];
        $peerId = $message['peer_id'];

        if ($peerId < 2000000000)
        {
            return $this->continue();
        }

        try {
            if ($this->isCommand($message['text']))
            {
                $handler = $this->getCommandHandler($message);
                $userId = $message['from_id'];
                if ($handler?->canUse($userId))
                {
                    $this->countCommandUse($peerId, $userId, );
                    $handler->handle();
                    return $this->break();
                }
            }

        }
        catch (\Exception $e)
        {
            $this->logger()->log(LogLevel::ERROR, $this->app()->formatException($e));
        }

        return $this->continue();
    }

    protected function isCommand(string $text): bool
    {
        return isset($text[0]) && $text[0] == '/';
    }

    protected function countCommandUse(string $peerId, string $userId)
    {
        /** @var PDO $db */
        $db = $this->app()->container()['db'];

        $stmt = $db->prepare("INSERT INTO `command_log` (peer_id, user_id, name, timestamp) VALUES (?, ?, ?, ?)");
        $stmt->execute([$peerId, $userId, $this->getCommandName(), time()]);

//        $stmt = $db->prepare("
//            INSERT IGNORE INTO `chat_user`
//            (`message_count`, `user_id`, `peer_id`) VALUES (1, ?, ?)
//            ON DUPLICATE KEY UPDATE `command_count` = `command_count` + 1");
//
//        $stmt->execute([$message['from_id'], $message['peer_id']]);
    }

    protected function getCommandHandler(array $message): ?AbstractCommand
    {
        $commandName = $this->getCommandName();

        if (!$this->commandExists($commandName))
        {
            return null;
        }
        return new ($this->getCommandMap()[$commandName])($this->app(), $message, $this->getCommandParams());
    }

    protected function commandExists(string $commandName): bool
    {
        return in_array($commandName, array_keys($this->getCommandMap()));
    }

    protected function getCommandMap(): array
    {
        return $this->app()->container()['commandMap'];
    }

    protected function getCommandName(): string
    {
        return $this->commandName;
    }

    protected function getCommandParams(): array
    {
        return $this->params;
    }
}
<?php

namespace App\Handler;

use PDO;

class MessageCounter extends AbstractHandler
{
    public function handle(): HandlerResult
    {
        /** @var PDO $db */
        $db = $this->app()->container()['db'];

        /** @var array $message */
        $message = $this->getInput('object')['message'];
        if ($message['peer_id'] < 2000000000)
        {
            return $this->continue();
        }

        $stmt = $db->prepare("INSERT INTO `message_log` (`user_id`, `peer_id`, `message`) VALUES (?, ?, ?)");
        $stmt->execute([$message['from_id'], $message['peer_id'], $message['text']]);
        return $this->continue();
    }
}
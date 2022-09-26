<?php

namespace App\Handler;

class DevModeGuard extends AbstractHandler
{
    public function handle(): HandlerResult
    {
        $devChatId = $_ENV['DEV_CHAT_ID'];
        if ($devChatId && $this->getMessage()['peer_id'] != $devChatId)
        {
            return $this->break();
        }

        return $this->continue();
    }
}
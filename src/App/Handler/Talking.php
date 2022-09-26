<?php

namespace App\Handler;

use App\Repository\MessageLog;

class Talking extends AbstractHandler
{
    public function handle(): HandlerResult
    {
        $message = $this->getMessage();
        if ($message['peer_id'] < 2000000000)
        {
            return $this->continue();
        }

        $text = $message['text'];
        if (str_contains(strtolower($text), 'сучара') || $this->isReplyToBot())
        {
            $text = $this->getResponseMessage();
            $this->logger()->debug($text ?: 'null');
            if ($text)
            {
                $this->logger()->debug($this->getMessage()['conversation_message_id']);
//                $this->sendReply($text, [
//                    'reply_to' => $this->getMessage()['conversation_message_id']
//                ]);
                $this->sendReply($text);
            }
        }

        return $this->continue();
    }

    public function getResponseMessage(): ?string
    {
        $msg = $this->getMessage();
        $messages = MessageLog::findLogsForPeerMember($msg['peer_id'], $msg['from_id']);
        $count = count($messages);
        $this->logger()->debug("{$msg['peer_id']}: {$count}");
        if ($count > 100)
        {
            return $messages[array_rand($messages)]['message'];
        }

        return null;
    }

    private function isReplyToBot(): bool
    {
        $replyMessage = $this->getMessage()['reply_message'] ?? null;

        return $replyMessage && $replyMessage['from_id'] == -$_ENV['VK_GROUP_ID'];
    }
}
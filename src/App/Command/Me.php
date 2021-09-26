<?php

namespace App\Command;

use JetBrains\PhpStorm\Pure;
use PDO;

class Me extends AbstractCommand
{
    public function handle(): void
    {
        $message = $this->message;
        $userInfo = $this->getUserInfo($message['peer_id'], $message['from_id']);

        $this->sendReply(sprintf(
            "%s\n%s\n%s",
            $this->getPidorCountString($userInfo[2]),
            $this->getCommandCountString($userInfo[1]),
            $this->getMessageCountString($userInfo[0])
        ));
    }

    protected function getUserInfo(int $peerId, int $userId): array
    {
        $stmt = $this->db()->prepare("
            SELECT COUNT(*) FROM `message_log` WHERE `user_id` = :userId AND `peer_id` = :peerId
            UNION ALL
            SELECT COUNT(*) FROM `command_log` WHERE `user_id` = :userId AND `peer_id` = :peerId
            UNION ALL
            SELECT COUNT(*) FROM `pidor_log` WHERE `user_id` = :userId AND `peer_id` = :peerId
        ");
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':peerId', $peerId, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(function ($item) {
            return $item[0];
        }, $stmt->fetchAll(PDO::FETCH_NUM));
    }

    #[Pure]
    protected function getPidorCountString(string $count): string
    {
        $lastNum = (int) $count[-1];

        if ((int) $count == 0)
        {
            return 'Ты не был пидором ни разу. Пидор.';
        }

        return "Ты был пидором $count {$this->getPluralizedCount($lastNum)}.";
    }

    #[Pure]
    public function getCommandCountString(string $count): string
    {
        return "Ты использовал команды $count {$this->getPluralizedCount($count[-1])}.";
    }

    protected function getMessageCountString(string $count): string
    {
        $lastNum = (int) $count[-1];
        if ($lastNum == 1)
        {
            $char = 'e';
        }
        else if ($lastNum > 1 && $lastNum < 5)
        {
            $char = 'я';
        }
        else
        {
            $char = 'й';
        }

        return "Ты напиздел $count сообщени$char.";
    }

    public function getPluralizedCount(int $count): string
    {
        if ($count > 1 && $count < 5)
        {
            return 'раза';
        }

        return 'раз';
    }
}
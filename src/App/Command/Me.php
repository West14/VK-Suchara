<?php

namespace App\Command;

use App;
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
            return App::phrase('you_was_not_pidor');
        }

        return App::phrase('you_was_pidor') . " $count {$this->getPluralizedCount($lastNum)}.";
    }

    #[Pure]
    public function getCommandCountString(string $count): string
    {
        return "Ты использовал команды $count {$this->getPluralizedCount($count[-1])}.";
    }

    protected function getMessageCountString(string $count): string
    {
        return sprintf(
            "%s $count %s.",
            App::phrase('you_talked'),
            App::phrase('message_' . App::pluralize((int) $count[-1]))
        );
    }

    public function getPluralizedCount(int $count): string
    {
        return App::phrase('count_' . App::pluralize($count));
    }
}
<?php

namespace App\Repository;

use App;
use PDO;

class MessageLog
{
    public static function findLogsForPeerMember(int $peerId, int $userId)
    {
        /** @var PDO $db */
        $db = App::app()->container()['db'];
        $stmt = $db->prepare("SELECT * FROM `message_log` WHERE `peer_id` = ? AND `user_id` = ?");
        $stmt->execute([$peerId, $userId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
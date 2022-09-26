<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 07.08.2021
 * Time: 16:24
 * Made with <3 by West from Bubuni Team
 */

namespace App\Command;

use App;
use VK\Exceptions\VKApiException;

class Pidor extends AbstractCommand
{
    /** @noinspection PhpUnhandledExceptionInspection */
    public function handle(): void
    {
        $todayPidor = $this->findTodayPidorForPeer();
        if ($todayPidor)
        {
            $this->sendReply(App::phrase('pidor_discovered') . $this->formatUserTag(
                $todayPidor, $this->getUserNameById($todayPidor))
            );
            return;
        }

        try {
            $memberList = $this->vkApi()->messages()->getConversationMembers($_ENV['VK_ACCESS_KEY'],
                [
                    'peer_id' => $this->message['peer_id']
                ])['profiles'];
        } catch (VKApiException $e)
        {
            if ($e->getErrorCode() == 917)
            {
                $this->sendReply("Гони админку, ебло");
                return;
            }
            throw $e;
        }

        $pidor = $memberList[array_rand($memberList)];
        $this->writePidor($pidor['id']);

        // temporary solution (no)
        // можно причесать, сделав систему эвентов, аля XF
        // просто подписываемся на эвент завершения, вызываться он будет после отправки ok и фцги функи
        // в идеале нужно изучить вопрос очередей (rabbitmq) и перевести на них, думаю, тут это применимо
        $t = $this;
        register_shutdown_function(function () use ($pidor, $t)
        {
            foreach ($this->getDetectorPhraseList() as $step)
            {
                $t->sendReply($step);
                $t->setTyping();
            }

            $t->sendReply($t->formatUserTag($pidor['id'], "{$pidor['first_name']} {$pidor['last_name']}"));

            $streakPhrase = $t->getPidorStreakPhrase($t->calculatePidorStreak());
            if ($streakPhrase)
            {
                $t->sendReply($streakPhrase);
            }
        });
    }

    public function findTodayPidorForPeer(): int
    {
        $stmt = $this->db()->prepare("SELECT `user_id` FROM `pidor_log` WHERE `peer_id` = ? AND `timestamp` >= ? LIMIT 1");
        $stmt->execute([$this->message['peer_id'], strtotime('today')]);

        return $stmt->fetchColumn();
    }

    public function calculatePidorStreak(): int
    {
        $stmt = $this->db()->prepare("SELECT `user_id` FROM `pidor_log` WHERE `peer_id` = ? ORDER BY `timestamp` DESC LIMIT 20");
        $stmt->execute([$this->message['peer_id']]);

        $i = 0;
        $latestPidorId = $stmt->fetchColumn();
        while ($userId = $stmt->fetchColumn())
        {
            if ($userId != $latestPidorId)
            {
                break;
            }
            $i++;
        }

        return $i;
    }

    public function writePidor(int $userId): void
    {
        $peerId = $this->message['peer_id'];

        $db = $this->db();
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO `pidor_log` (peer_id, user_id, timestamp) VALUES (?, ?, ?)");
        $stmt->execute([$peerId, $userId, time()]);

        $stmt = $db->prepare("
                INSERT IGNORE INTO `chat_user` 
                (pidor_total, user_id, peer_id) VALUES (1, ?, ?) 
                ON DUPLICATE KEY UPDATE `pidor_total` = `pidor_total` + 1");
        $stmt->execute([$userId, $peerId]);

        $db->commit();
    }

    public function getDetectorPhraseList(): array
    {
        return [
            App::phrase('pidor_search_start'),
            App::phrase('pidor_search_middle'),
            App::phrase('pidor_search_finisher')
        ];
    }

    public function getPidorStreakPhrase(int $streak): ?string
    {
        if (!$streak)
        {
            return null;
        }

        return $streak > 9
            ? App::phrase('pidor_streak_else')
            : App::phrase('pidor_streak_' . $streak + 1);
    }
}
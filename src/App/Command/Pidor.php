<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 07.08.2021
 * Time: 16:24
 * Made with <3 by West from Bubuni Team
 */

namespace App\Command;

use VK\Exceptions\VKApiException;

class Pidor extends AbstractCommand
{
    /** @noinspection PhpUnhandledExceptionInspection */
    public function handle(): void
    {
        $todayPidor = $this->findTodayPidorForPeer();
        if ($todayPidor)
        {
            $this->sendReply("Сегодняшний пидор уже обнаружен: " . $this->formatUserTag(
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
                $t->sendReply($step[array_rand($step)]);
                $t->setTyping();
            }

            $t->sendReply($t->formatUserTag($pidor['id'], "{$pidor['first_name']} {$pidor['last_name']}"));
        });
    }

    public function findTodayPidorForPeer(): int
    {
        $stmt = $this->db()->prepare("SELECT `user_id` FROM `pidor_log` WHERE `peer_id` = ? AND `timestamp` >= ? LIMIT 1");
        $stmt->execute([$this->message['peer_id'], strtotime('today')]);

        return $stmt->fetchColumn();
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
            [
                'Загоняем всех пидоров в вольер',
                'Все пидоры в одном помещении',
                'Вы, не совсем натуралы. Я бы даже сказал совсем НЕ натуралы.',
                'Собрание в церкви святого пидора начинается',
                'Я собрал всех пидоров вместе',
                'Петушки собрались в баре "Голубая устрица"',
                'Все пидорки увязли в дурно пахнущем болоте',
                'Голубки, внимание! Ух, как вас много',
                'Из Техаса к нам присылают только быков и пидорасов. Рогов я у вас не вижу, так что выбор невелик',
                'ПИДОРЫ, приготовьте свои грязные сральники к разбору полетов!',
                'Объявляю построение пидорской роты!'
            ],
            [
                'Ищем самого возбужденного',
                'Главный сегодня только один',
                'Город засыпает, просыпается главный пидор',
                'Архипидору не скрыться',
                'У одного задок сегодня послабее',
                'Ооо, побольше бы таких в нашем клубе',
                'Сегодня Индиана Джонс в поисках утраченного пидрилы',
                'Кому-то из вас сегодня ковырнули скорлупку',
                'У кого-то дымоход почище остальных',
                'У одного из вас коптильня подогрета',
                'На грязевые ванные отправляется лишь один'
            ],
            [
                'ХОБАНА! Вижу блеск в глазах…',
                'Воу-воу, полегче…',
                'Глину месить, это тебе не в тапки ссать…',
                'ТЫ ЧО ДЫРЯВЫЙ',
                'Поппенгаген открыт для всех желающих у…',
                'Лупится в туза, но не играет в карты',
                'Вонзается плугом в тугой чернозём',
                'Любитель сделать мясной укол',
                'Не лесник, но шебуршит в дупле',
                'Кожаная пуля в кожаном стволе у...',
                'Шышл-мышл, пёрнул спермой, вышел'
            ]
        ];
    }
}
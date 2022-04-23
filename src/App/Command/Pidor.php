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

    public function getPidorStreakPhrase(int $streak): ?string
    {
        $phraseMap = [
            null,
            [
                "Посмотрите на него, уже дважды подряд пидарок. Неплохой результат, но ты можешь лучше.",
                "Хуя, два раза подряд пидор? Срочно звони мамке, пусть похвастается перед соседками",
                "Однажды пидор — дважды пидор, поздравляю"
            ],
            [
                "Ну, три раза подряд пидор это уже неплохо, время написать разработчику и зарепортить баг (нет)",
                "Хуя, трижды это уже охуенно! По статистике из интернета только каждый сотый становится тройным пидором!",
                "Не гордись собой особо, но ты уже трижды как пидор, молодец. Может бахнешь мне донатик по такому случаю?"
            ],
            [
                "Уже 4 раза пидор.",
                "Четверый подряд, ты заебал",
                "Поздравляю с 4 пидорскими днями подряд"
            ],
            [
                "Мог бы вообще-то и с друзьями поделиться как ты предпочитаешь делиться своим очком",
                "5-й раз не пидорас (ладно, пидорас)",
                "5 дней подряд в этом чате никто не удивлен, что ты пидор"
            ],
            [
                "Ладно, сдаюсь, ты 6-й пидор потому твои друзья (тоже пидоры) задонатили чтобы ты выпадал",
                "Ты в курсе, что это все твои дружки-петушки занесли чтобы ты выпадал уже 6 раз подряд?",
                "Шестой день подряд так просто не бывает, может быть это твои дружки занесли бабосиков? Проверь."
            ],
            [
                "На седьмой день бог успел сотворить землю, а ты все еще не успел убедиться, что ты — пидор. ",
                "Семь это счастливое число, но несчастливое для тех, кто седьмой день подряд пидор.",
                "Кстати, я наврал, что за тебя занесли денюжку твои пидорки, еще бы кто в этом мире за тебя платил."
            ],
            [
                "Exception in thread \"main\" java.lang.RuntimeException: Pidor 8th day in a row",
                "Traceback (most recent call last):\nFile \"/pidor_searcher\", line 4, in <module> findPidor()\nPidorError: Pidor 8th day in a row",
                "Library API: Exception caught in function 'find_pidor'\nBacktrace:\n~/src/detail/Pidor.cpp:17 : Pidor 8th day in a row"
            ],
            [
                "Ладно, ты заебал. В следующий день выберу пидором кого-то другого, ты уже 9 раз подряд забираешь сей гордый титул",
                "Никогда еще не видел такого грандиозного пидора, 9(!!!) дней подряд, охуеть!",
                "Ты принц пидарасов"
            ],
            [
                "10 раз подряд.",
                "Уже 10-й раз подряд ты пидор, грац.",
                "Ну и че, кого нынче можно удивить пидором 10 раз подряд? Хап тьфу"
            ]
        ];

        if ($streak > 9)
        {
            $phraseSet = [
                "Поздравляю, вы прошли эту игру. Выберите пидора еще раз чтобы начать заново.",
                "Вы прошли Сучару, поделитесь на форуме советами по прохождению",
                "Титры: \nРазработчик: вас ебать не должно\nСценарист: разработчик\nОсобые благодарности вашим матерям за качественные услуги\nThe end."
            ];
        }
        else
        {
            $phraseSet = $phraseMap[$streak];
        }
        return $phraseSet ? $phraseSet[array_rand($phraseSet)] : null;
    }
}
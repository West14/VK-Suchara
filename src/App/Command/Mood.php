<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 07.08.2021
 * Time: 13:51
 * Made with <3 by West from Bubuni Team
 */

namespace App\Command;

class Mood extends AbstractCommand
{
    public function handle(): void
    {
        $this->sendReply('Какой ты сегодня?');
        sleep(3);
        $this->sendReply('', [
            'attachment' => 'photo-' . $_ENV['VK_GROUP_ID'] . '_' . $this->getRandomMood()
        ]);
    }

    public function getRandomMood(): string
    {
        $moodImages = explode(',', $_ENV['VK_MOOD_IMAGE_IDS']);
        return $moodImages[array_rand($moodImages)];
    }
}
<?php

namespace App\Command;

class Help extends AbstractCommand
{
    public function handle(): void
    {
        $this->sendReply("
            /pidor - Сегодняшний пидор
            /me - Ваша пидорская статистика
            /today - Какой ты пидор
        ");
    }
}
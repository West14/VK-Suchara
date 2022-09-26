<?php

namespace App\Command;

use App;

class Help extends AbstractCommand
{
    public function handle(): void
    {
        $this->sendReply(App::phrase('help'));
    }
}
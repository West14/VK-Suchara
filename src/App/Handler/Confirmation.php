<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 07.08.2021
 * Time: 2:50
 * Made with <3 by West from Bubuni Team
 */

namespace App\Handler;

class Confirmation extends AbstractHandler
{
    public function handle(): HandlerResult
    {
        if ($this->getInput('group_id') == $_ENV['VK_GROUP_ID'])
        {
            return $this->break($_ENV['VK_CONFIRMATION_STRING']);
        }

        return $this->break();
    }
}
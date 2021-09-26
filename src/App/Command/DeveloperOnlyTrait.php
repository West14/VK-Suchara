<?php

namespace App\Command;

trait DeveloperOnlyTrait
{
    public function canUse(int $userId): bool
    {
        return $userId == $_ENV['VK_DEVELOPER_ID'];
    }
}
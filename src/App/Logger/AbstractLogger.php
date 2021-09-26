<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 08.08.2021
 * Time: 21:07
 * Made with <3 by West from Bubuni Team
 */

namespace App\Logger;

abstract class AbstractLogger extends \Psr\Log\AbstractLogger
{
    public function setup(): bool
    {
        return true;
    }
}
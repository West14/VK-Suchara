<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 08.08.2021
 * Time: 22:30
 * Made with <3 by West from Bubuni Team
 */

namespace App\Command;

use Exception;

class Log extends AbstractCommand
{
    use DeveloperOnlyTrait;

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        throw new Exception('test');
    }
}
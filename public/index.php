<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 07.08.2021
 * Time: 1:04
 * Made with <3 by West from Bubuni Team
 */

$appRoot = __DIR__ . '/../';
require_once $appRoot . 'src/App.php';

App::setup($appRoot)->run();

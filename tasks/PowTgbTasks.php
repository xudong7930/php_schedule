<?php

use Crunz\Schedule;

$schedule = new Schedule;

$file = realpath(dirname(__FILE__) . '/../apps/pow_taoguba.php');

$schedule->run(PHP_BINARY . " {$file}")
    ->cron('30 18 * * *')
    ->appendOutputTo('/tmp/php_schedule.log')
    ->description('拉取淘股吧老猪炒股');
return $schedule;
<?php

use Crunz\Schedule;

$schedule = new Schedule;

$file = realpath(dirname(__FILE__) . '/../apps/pow_wanghou.php');

$schedule->run(PHP_BINARY . " {$file}")
    ->cron('*/3 * * * *')
    ->appendOutputTo('/tmp/php_schedule.log')
    ->description('拉取网猴羊毛');
return $schedule;
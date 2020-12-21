<?php

use Crunz\Schedule;

$schedule = new Schedule;

$file = realpath(dirname(__FILE__) . '/../apps/hot_stock_notify.php');

$schedule->run(PHP_BINARY . " {$file}")
    ->cron('40 15 * * *')
    ->appendOutputTo('/tmp/php_schedule.log')
    ->description('发送股票统计结果');
return $schedule;
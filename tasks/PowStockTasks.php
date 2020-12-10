<?php

use Crunz\Schedule;

$schedule = new Schedule;

$file = realpath(dirname(__FILE__) . '/../apps/pow_stock.php');

$schedule->run(PHP_BINARY . " {$file}")
    ->cron('30 15 * * *')
    ->appendOutputTo('/tmp/php_schedule.log')
    ->description('拉取有为财经个股资金流入流出榜单');
return $schedule;
<?php

use Crunz\Schedule;

$schedule = new Schedule;

$file = realpath(dirname(__FILE__) . '/../apps/pow_mmym.php');

$schedule->run(PHP_BINARY . " {$file}")
    ->cron('*/2 * * * *')
    ->appendOutputTo('/tmp/php_schedule.log')
    ->description('拉取买买羊毛线报');
return $schedule;
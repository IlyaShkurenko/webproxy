<?php

namespace Vendor\Jobby;

use Cron\CronExpression;
use DateTime;

class ScheduleChecker extends \Jobby\ScheduleChecker
{
    public function isDueTime($schedule, $timestamp = 0)
    {
        if (!$timestamp) {
            $timestamp = time();
        }

        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $schedule);
        if ($dateTime !== false) {
            return $dateTime->format('Y-m-d H:i') == (date('Y-m-d H:i', $timestamp));
        }

        return CronExpression::factory((string)$schedule)->isDue((new DateTime())->setTimestamp($timestamp));
    }
}

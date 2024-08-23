<?php
namespace wing\libs;
use \DateTime;

/**
 * A Date helper library
 *
 * @author Eric Wang,<fonqing@gmail.com>
 * @copyright Aomasoft co.,Ltd. 2021
 * @version 1
 */

class DatePlus
{

    /**
     * Special work days
     *
     * @var array
     */
    public static array $workdays = [];

    /**
     * Holidays list
     *
     * @var array
     */
    public static array $holidays = [];

    /**
     * Find whether a day is workday or holiday
     *
     * @param string|integer|DateTime $date The date
     * @return boolean
     */
    public static function isWorkDay(mixed $date): bool
    {
        $time = self::parseTimeArg($date);
        $date = date('Y-m-d', $time);
        if (in_array($date, self::$workdays)) {
            return true;
        }
        if (in_array($date, self::$holidays)) {
            return false;
        }
        $week = date('w', $time);
        return ($week > 0 && $week < 6);
    }

    /**
     * Find whether a datetime string is valid
     *
     * @param string $string datetime string
     * @param string $format Format
     *
     * @return bool
     */
    public static function isValidDate(string $string, string $format = 'Y-m-d'): bool
    {
        $string = trim($string);
        $format = trim($format);
        $result = date($format, strtotime($string));
        return $string === $result;
    }

    /**
     * Create a month list 
     *
     * @param string $start start year and month
     * @param string $end end year and month
     * @return array
     * 
     * <code lang="php">
     * $list = DatePlus::getMonthList('2021-01', '2021-12');
     * </code>
     */
    public static function getMonthList(string $start, string $end): array
    {
        $start = strtotime($start . '-01');
        $end = strtotime($end . '-01');
        if ($start > $end) {
            return [];
        }
        $data = [];
        $data[] = date('Y-m', $start);
        while (($start = strtotime('+1 month', $start)) <= $end) {
            $data[] = date('Y-m', $start);
        }
        return $data;
    }

    /**
     * Create day list 
     *
     * @param string|integer|DateTime $start Start date
     * @param string|integer|DateTime $end End date
     * @param boolean $timestamp if true will return a timestamp in integer
     * @return array
     * <code>
     * $list = DatePlus::getDayList('2021-10-01', '2021-10-03');
     * //Will get array bellow
     * //['2021-10-01', '2021-10-02', '2021-10-03']
     * </code>
     */
    public static function getDayList(mixed $start, mixed $end, bool $timestamp = false): array
    {
        $start = self::parseTimeArg($start);
        $end = self::parseTimeArg($end);
        if ($start > $end) {
            return [];
        }
        $data = [];
        $data[] = $timestamp ? $start : date('Y-m-d', $start);
        while (($start = strtotime('+1 day', $start)) <= $end) {
            $data[] = $timestamp ? $start : date('Y-m-d', $start);
        }
        return $data;
    }

    /**
     * Create time list
     *
     * @param string|integer|DateTime $start Start date
     * @param string|integer|DateTime $end End date
     * @param integer $size Duration
     * @param boolean $timestamp 
     * @return array
     * 
     * <code>
     * $list = DatePlus::getTimeList('2021-10-01 8:00:00', '2021-10-01 10:00:00');
     * //Will get array bellow
     * //['8:00:00', '9:00:00', '10:00:00']
     * </code>
     */
    public static function getTimeList(mixed $start, mixed $end, int $size = 3600, bool $timestamp = false): array
    {
        $time1 = self::parseTimeArg($start);
        $time2 = self::parseTimeArg($end);
        if ($time1 > $time2) {
            return [];
        }
        $data = [];
        $data[] = $timestamp ? $time1 : date('H:i', $time1);
        while (($time1 = $time1 + $size) <= $time2) {
            $data[] = $timestamp ? $time1 : date('H:i', $time1);
        }
        return $data;
    }

    /**
     * Get weekday Chinese name
     *
     * @param string|integer|DateTime $dateString
     * @param bool $short
     * @return string
     */
    public static function getWeekName(mixed $dateString, bool $short = false): string
    {
        $timestamp = self::parseTimeArg($dateString);
        if ($timestamp < 1) {
            return '';
        }
        $weekNames = [
            ['日', '星期日'],
            ['一', '星期一'],
            ['二', '星期二'],
            ['三', '星期三'],
            ['四', '星期四'],
            ['五', '星期五'],
            ['六', '星期六'],
        ];
        return $weekNames[(int) date('w', $timestamp)][$short ? 0 : 1] ?? '';
    }

    /**
     * Get week start date and weekend date by a given date
     *
     * Attention: Start on Monday
     *
     * @param int|string|DateTime $time
     * @param bool $timestamp
     * @return array
     */
    public static function weekRange(mixed $time = 0, bool $timestamp = false): array
    {
        $time = $time ? self::parseTimeArg($time) : time();
        if ($time < 1) {
            $time = time();
        }
        $week = (int) date('N', $time);
        $mon = $time - ($week - 1) * 86400;
        $sun = $time + abs($week - 7) * 86400;
        return $timestamp ? [$mon, $sun] : [date('Y-m-d', $mon), date('Y-m-d', $sun)];
    }

    /**
     * Fit different time format to timestamp
     *
     * @param mixed $arg
     * @return int
     */
    private static function parseTimeArg(mixed $arg): int
    {
        if (is_string($arg)) {
            return strtotime($arg);
        } elseif (!is_scalar($arg) && $arg instanceof DateTime) {
            return $arg->getTimestamp();
        } elseif (is_int($arg)) {
            return $arg;
        } else {
            throw new \InvalidArgumentException('Invalid argument type');
        }
    }

}

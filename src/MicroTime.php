<?php

namespace Tomkirsch\MicroTime;

use CodeIgniter\I18n\Time;
use Locale;
use DateTime;
use DateTimeZone;
use DateTimeInterface;
use DateInterval;
use CodeIgniter\I18n\Exceptions\I18nException;
use Exception;

class MicroTime extends Time
{
    /**
     * Intl (local) date format. Do not change.
     */
    const FORMAT_INTL = "yyyy-MM-dd HH:mm:ss.SSS";

    /**
     * DateTime format. Do not change.
     */
    const FORMAT_DATETIME = "Y-m-d H:i:s.u";

    /**
     * Create a float from a datetime string, so microseconds are preserved
     */
    public static function strtotime(string $timeString): float
    {
        $dt = new DateTime($timeString);
        return (float) $dt->format('U.u');
    }

    /**
     * Override to add microseconds
     * 
     * @param DateTimeZone|string|null $timezone
     */
    public function __construct(?string $time = null, $timezone = null, ?string $locale = null)
    {
        // set string format to the constant
        $this->toStringFormat = self::FORMAT_INTL;

        $this->locale = $locale ?: Locale::getDefault();

        $time ??= '';

        // If a test instance has been provided, use it instead.
        if ($time === '' && static::$testNow instanceof self) {
            if (
                $timezone !== null
            ) {
                $testNow = static::$testNow->setTimezone($timezone);
                $time    = $testNow->format(self::FORMAT_DATETIME);
            } else {
                $timezone = static::$testNow->getTimezone();
                $time     = static::$testNow->format(self::FORMAT_DATETIME);
            }
        }

        $timezone       = $timezone ?: date_default_timezone_get();
        $this->timezone = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);

        // If the time string was a relative string (i.e. 'next Tuesday')
        // then we need to adjust the time going in so that we have a current
        // timezone to work with.
        if ($time !== '' && static::hasRelativeKeywords($time)) {
            $instance = new DateTime('now', $this->timezone);
            $instance->modify($time);
            $time = $instance->format(self::FORMAT_DATETIME);
        }

        parent::__construct($time, $this->timezone);
    }

    /**
     * Returns a new instance with the date set to today, and the time set to the values passed in.
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return self
     *
     * @throws Exception
     */
    public static function createFromTime(?int $hour = null, ?int $minutes = null, ?int $seconds = null, $timezone = null, ?string $locale = null, ?int $microseconds = null)
    {
        return static::create(null, null, null, $hour, $minutes, $seconds, $timezone, $locale, $microseconds);
    }

    /**
     * Returns a new instance with the date time values individually set.
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return self
     *
     * @throws Exception
     */
    public static function create(?int $year = null, ?int $month = null, ?int $day = null, ?int $hour = null, ?int $minutes = null, ?int $seconds = null, $timezone = null, ?string $locale = null, ?int $microseconds = null)
    {
        $year ??= date('Y');
        $month ??= date('m');
        $day ??= date('d');
        $hour    = empty($hour) ? 0 : $hour;
        $minutes = empty($minutes) ? 0 : $minutes;
        $seconds = empty($seconds) ? 0 : $seconds;
        $microseconds = empty($microseconds) ? 0 : $microseconds;

        return new self("{$year}-{$month}-{$day} {$hour}:{$minutes}:{$seconds}.{$microseconds}", $timezone, $locale);
    }

    /**
     * Provides a replacement for DateTime's own createFromFormat function, that provides
     * more flexible timeZone handling
     *
     * @param string                   $format
     * @param string                   $datetime
     * @param DateTimeZone|string|null $timezone
     *
     * @return self
     *
     * @throws Exception
     */
    #[ReturnTypeWillChange]
    public static function createFromFormat($format, $datetime, $timezone = null)
    {
        if (!$date = parent::createFromFormat($format, $datetime)) {
            throw I18nException::forInvalidFormat($format);
        }

        return new self($date->format(self::FORMAT_DATETIME), $timezone);
    }

    /**
     * Returns a new instance with the datetime set based on the provided UNIX timestamp (with microseconds floating point)
     *
     * @param float $timestamp
     * @param DateTimeZone|string|null $timezone
     *
     * @return self
     *
     * @throws Exception
     */
    public static function createFromTimestampFloat(float $timestamp, $timezone = null, ?string $locale = null)
    {
        $timestampInt = (int) $timestamp;
        $time = new self(gmdate('Y-m-d H:i:s', $timestamp), 'UTC', $locale);
        $time = $time->setMicrosecond($timestamp - $timestampInt);
        $timezone ??= 'UTC';

        return $time->setTimezone($timezone);
    }

    /**
     * Takes an instance of DateTimeInterface and returns an instance of Time with it's same values.
     *
     * @return self
     *
     * @throws Exception
     */
    public static function createFromInstance(DateTimeInterface $dateTime, ?string $locale = null)
    {
        $date     = $dateTime->format(self::FORMAT_DATETIME);
        $timezone = $dateTime->getTimezone();

        return new self($date, $timezone, $locale);
    }

    /**
     * Converts the current instance to a mutable DateTime object.
     *
     * @return DateTime
     *
     * @throws Exception
     */
    public function toDateTime()
    {
        $dateTime = DateTime::createFromImmutable($this);
        return $dateTime;
    }

    /**
     * Gets the time() value for the current instance, including microseconds
     */
    public function getTimestampMicro(): float
    {
        $micro = (float) self::format('.u');
        return self::getTimestamp() + $micro;
    }

    /**
     * Return the localized seconds
     *
     * @throws Exception
     */
    public function getMicrosecond(): string
    {
        return $this->toLocalizedString('u');
    }

    /**
     * Sets the microsecond of the second.
     *
     * @param int|string $value
     *
     * @return self
     *
     * @throws Exception
     */
    public function setMicrosecond($value)
    {
        if ($value < 0 || $value > 999999) {
            throw I18nException::forInvalidSeconds($value);
        }
        return $this->setValue('microsecond', $value);
    }

    /**
     * Helper method to do the heavy lifting of the 'setX' methods.
     *
     * @param int $value
     *
     * @return self
     *
     * @throws Exception
     */
    protected function setValue(string $name, $value)
    {
        [$year, $month, $day, $hour, $minute, $second, $microsecond] = explode('-', $this->format('Y-n-j-G-i-s-u'));

        ${$name} = $value;

        return self::create(
            (int) $year,
            (int) $month,
            (int) $day,
            (int) $hour,
            (int) $minute,
            (int) $second,
            $this->getTimezoneName(),
            $this->locale,
            (int) $microsecond
        );
    }

    /**
     * Returns a new instance with the date set to the new timestamp.
     *
     * @param int $timestamp
     *
     * @return self
     *
     * @throws Exception
     */
    #[ReturnTypeWillChange]
    public function setTimestamp($timestamp)
    {
        $time = date(self::FORMAT_DATETIME, $timestamp);

        return self::parse($time, $this->timezone, $this->locale);
    }


    /**
     * Returns a new Time instance with $seconds added to the time.
     *
     * @return static
     */
    public function addMicroseconds(int $microseconds)
    {
        $time = clone $this;
        return $time->add(DateInterval::createFromDateString("{$microseconds} microseconds"));
    }

    /**
     * Returns a new Time instance with $seconds subtracted from the time.
     *
     * @return static
     */
    public function subMicroseconds(int $microseconds)
    {
        $time = clone $this;
        return $time->sub(DateInterval::createFromDateString("{$microseconds} microseconds"));
    }

    /**
     * Returns the localized value of the date in the format 'Y-m-d H:i:s.SSS'
     *
     * @throws Exception
     */
    public function toDateTimeString()
    {
        return $this->toLocalizedString(self::FORMAT_INTL);
    }

    /**
     * Returns a localized version of the time in nicer date format:
     *
     *  i.e. 13:20:33.67889
     *
     * @return string
     *
     * @throws Exception
     */
    public function toTimeString()
    {
        return $this->toLocalizedString('HH:mm:ss.SSS');
    }

    /**
     * Outputs a short format version of the datetime.
     * The output is NOT localized intentionally.
     */
    public function __toString(): string
    {
        return $this->format(self::FORMAT_DATETIME);
    }
}

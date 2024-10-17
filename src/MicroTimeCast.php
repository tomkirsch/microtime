<?php

namespace Tomkirsch\MicroTime;

use CodeIgniter\Entity\Cast\BaseCast;
use DateTimeInterface;

class MicroTimeCast extends BaseCast
{
    public static function get($value, array $params = [])
    {
        if ($value instanceof MicroTime) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return MicroTime::createFromInstance($value);
        }

        if (is_numeric($value)) {
            return MicroTime::createFromTimestamp($value);
        }

        if (is_string($value)) {
            return new MicroTime($value);
        }

        return $value;
    }
}

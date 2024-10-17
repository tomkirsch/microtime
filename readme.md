# MicroTime

Extends CI's Time class to provide microseconds.
Any methods that pass hour, seconds, etc. now have microsecond int as the last arg.
PHP's format for microseconds is `.u`
MySQL field should be DATETIME(6)

```
use Tomkirsch\MicroTime\MicroTime;

$time = new MicroTime();
$time->setMicrosecond(6666);
print $time; // 2024-04-22 04:12:22.6666
```

## Casting in Entities

```
use Tomkirsch\MicroTime\MicroTime;

class Transaction extends Entity{
    protected $castHandlers = [
        "microtime" => MicroTimeCast::class,
    ];
    protected $casts = [
		'trans_date'		=> 'microtime',
	];
}
```

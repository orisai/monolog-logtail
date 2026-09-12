<?php declare(strict_types = 1);

namespace Orisai\MonologLogtail;

use DateTimeInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;
use function assert;
use function is_array;
use function is_int;
use function is_string;
use function strtolower;
use function ucfirst;

final class LogtailFormatter extends NormalizerFormatter
{

	/**
	 * @param array<mixed>|LogRecord $record
	 * @return array<string, mixed>
	 */
	public function format($record): array
	{
		if ($record instanceof LogRecord) {
			$datetime = $record->datetime;
			$message = $record->message;
			$levelName = $record->level->name;
			$levelValue = $record->level->value;
			$channel = $record->channel;
			$context = $record->context;
			$extra = $record->extra;
		} else {
			$datetime = $record['datetime'];
			assert($datetime instanceof DateTimeInterface);

			$message = $record['message'];
			assert(is_string($message));

			$levelName = $record['level_name'];
			assert(is_string($levelName));
			$levelName = ucfirst(strtolower($levelName));

			$levelValue = $record['level'];
			assert(is_int($levelValue));

			$channel = $record['channel'];
			assert(is_string($channel));

			$context = $record['context'];
			assert(is_array($context));

			$extra = $record['extra'];
			assert(is_array($extra));
		}

		return [
			'dt' => $datetime->format(DateTimeInterface::ATOM),
			'message' => $message,
			'level' => $levelName,
			'level_value' => $levelValue,
			'channel' => $channel,
			'context' => $this->normalize($context),
			'extra' => $this->normalize($extra),
		];
	}

}

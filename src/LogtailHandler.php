<?php declare(strict_types = 1);

namespace Orisai\MonologLogtail;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\BufferHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Psr\Log\LogLevel;
use Throwable;
use function assert;
use function is_array;
use function register_shutdown_function;

final class LogtailHandler extends AbstractProcessingHandler
{

	private LogtailClient $client;

	/** @var array<array<mixed>> */
	private array $records = [];

	/**
	 * @param int|string|Level|LogLevel::* $level
	 * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $level
	 */
	public function __construct(LogtailClient $client, $level = Logger::DEBUG, bool $bubble = true)
	{
		parent::__construct($level, $bubble);
		$this->client = $client;

		// __destruct() is not called on fatal errors
		register_shutdown_function(fn () => $this->saveClose());
	}

	/**
	 * @param array<mixed>|LogRecord $record
	 */
	protected function write($record): void
	{
		$formatted = $record instanceof LogRecord ? $record->formatted : $record['formatted'];
		assert(is_array($formatted));

		$this->records[] = $formatted;
	}

	public function handleBatch(array $records): void
	{
		parent::handleBatch($records);
		$this->flush();
	}

	private function flush(): void
	{
		if ($this->records !== []) {
			$this->client->log($this->records);
			$this->records = [];
		}
	}

	public function close(): void
	{
		$this->flush();

		parent::close();
	}

	private function saveClose(): void
	{
		try {
			$this->close();
		} catch (Throwable $throwable) {
			// Error must not be shown, because shutdown may happen after error handler shutdown
		}
	}

	public function reset(): void
	{
		$this->flush();

		parent::reset();
	}

	protected function getDefaultFormatter(): FormatterInterface
	{
		return new LogtailFormatter();
	}

	/**
	 * @see BufferHandler::__destruct()
	 */
	public function __destruct()
	{
		// Parent is not called intentionally
	}

}

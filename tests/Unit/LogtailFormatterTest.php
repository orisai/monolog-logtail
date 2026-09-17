<?php declare(strict_types = 1);

namespace Tests\Orisai\MonologLogtail\Unit;

use DateTimeImmutable;
use DateTimeInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Orisai\MonologLogtail\LogtailClient;
use Orisai\MonologLogtail\LogtailFormatter;
use Orisai\MonologLogtail\LogtailHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Tests\Orisai\MonologLogtail\Unit\Fixtures\ResponseQueue;
use function array_intersect_key;
use function array_keys;
use function json_decode;
use const JSON_THROW_ON_ERROR;

final class LogtailFormatterTest extends TestCase
{

	public function testSentRecords(): void
	{
		$httpClient = new ResponseQueue();
		$handler = new LogtailHandler(
			new LogtailClient('token', 'https://s1.example.betterstackdata.com/', new MockHttpClient($httpClient)),
		);

		$logger = new Logger('app');
		$logger->pushHandler($handler);

		$logger->info('hello', [
			'exception' => new RuntimeException('boom', 3),
			'user' => ['id' => 1],
		]);
		$logger->error('bad');

		$handler->close();

		$requests = $httpClient->getRequests();
		self::assertCount(1, $requests);

		$request = $requests[0];
		self::assertContains('Authorization: Bearer token', $request['options']['headers']);
		self::assertContains('Content-Type: application/json', $request['options']['headers']);

		$records = json_decode($httpClient->getBodies()[0], true, 512, JSON_THROW_ON_ERROR);
		self::assertIsArray($records);
		self::assertCount(2, $records);

		$first = $records[0];
		self::assertIsArray($first);
		self::assertSame(
			['dt', 'message', 'level', 'level_value', 'channel', 'context', 'extra'],
			array_keys($first),
		);

		$dt = $first['dt'];
		self::assertIsString($dt);
		self::assertMatchesRegularExpression('~^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$~', $dt);
		self::assertSame('hello', $first['message']);
		self::assertSame('Info', $first['level']);
		self::assertSame(200, $first['level_value']);
		self::assertSame('app', $first['channel']);
		self::assertSame([], $first['extra']);

		$context = $first['context'];
		self::assertIsArray($context);
		self::assertSame(['exception', 'user'], array_keys($context));
		self::assertSame(['id' => 1], $context['user']);

		$exception = $context['exception'];
		self::assertIsArray($exception);
		self::assertSame(
			[
				'class' => RuntimeException::class,
				'message' => 'boom',
				'code' => 3,
			],
			array_intersect_key($exception, [
				'class' => null,
				'message' => null,
				'code' => null,
			]),
		);

		$file = $exception['file'];
		self::assertIsString($file);
		self::assertMatchesRegularExpression('~^.+\.php:\d+$~', $file);
		self::assertArrayHasKey('trace', $exception);

		$second = $records[1];
		self::assertIsArray($second);
		self::assertSame(
			['dt', 'message', 'level', 'level_value', 'channel', 'context', 'extra'],
			array_keys($second),
		);
		self::assertSame('bad', $second['message']);
		self::assertSame('Error', $second['level']);
		self::assertSame(400, $second['level_value']);
		self::assertSame('app', $second['channel']);
		self::assertSame([], $second['context']);
		self::assertSame([], $second['extra']);
	}

	public function testFormat(): void
	{
		$testHandler = new TestHandler();
		$logger = new Logger('channel');
		$logger->pushHandler($testHandler);
		$logger->warning('msg', ['a' => 'b']);

		$records = $testHandler->getRecords();
		self::assertCount(1, $records);

		$record = $records[0];
		$datetime = $record['datetime'];
		self::assertInstanceOf(DateTimeImmutable::class, $datetime);

		$formatter = new LogtailFormatter();

		self::assertSame(
			[
				'dt' => $datetime->format(DateTimeInterface::ATOM),
				'message' => 'msg',
				'level' => 'Warning',
				'level_value' => 300,
				'channel' => 'channel',
				'context' => ['a' => 'b'],
				'extra' => [],
			],
			$formatter->format($record),
		);
	}

}

<?php declare(strict_types = 1);

namespace Tests\Orisai\MonologLogtail\Unit;

use Monolog\Handler\BufferHandler;
use Monolog\Logger;
use Orisai\MonologLogtail\LogtailClient;
use Orisai\MonologLogtail\LogtailHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Tests\Orisai\MonologLogtail\Unit\Fixtures\ResponseQueue;
use function array_column;
use function json_decode;
use const JSON_THROW_ON_ERROR;

final class LogtailHandlerTest extends TestCase
{

	public function testLazy(): void
	{
		$client = new LogtailClient('token', 'https://s1.example.betterstackdata.com/');
		$handler = new LogtailHandler($client);

		$handler->close();
		$handler->reset();

		// Just to make phpunit happy
		self::assertTrue(true);
	}

	public function testResetSendsBufferedRecordsOnce(): void
	{
		$httpClient = new ResponseQueue();
		$handler = new LogtailHandler(
			new LogtailClient('token', 'https://s1.example.betterstackdata.com/', new MockHttpClient($httpClient)),
		);

		$logger = new Logger('app');
		$logger->pushHandler($handler);
		$logger->info('one');

		$handler->reset();
		$afterFirstReset = $httpClient->getRequests();

		$handler->reset();
		$afterSecondReset = $httpClient->getRequests();

		self::assertCount(1, $afterFirstReset);
		self::assertCount(1, $afterSecondReset);
	}

	public function testHandleBatchSendsImmediately(): void
	{
		$httpClient = new ResponseQueue();
		$handler = new LogtailHandler(
			new LogtailClient('token', 'https://s1.example.betterstackdata.com/', new MockHttpClient($httpClient)),
		);
		$buffer = new BufferHandler($handler, 2, Logger::DEBUG, true, true);

		$logger = new Logger('app');
		$logger->pushHandler($buffer);
		$logger->info('one');
		$logger->info('two');
		$beforeOverflow = $httpClient->getRequests();
		self::assertCount(0, $beforeOverflow);

		$logger->info('three');
		$requests = $httpClient->getRequests();
		self::assertCount(1, $requests);
		self::assertSame(
			['one', 'two'],
			array_column(json_decode($httpClient->getBodies()[0], true, 512, JSON_THROW_ON_ERROR), 'message'),
		);

		$buffer->flush();
		$requests = $httpClient->getRequests();
		self::assertCount(2, $requests);
		self::assertSame(
			['three'],
			array_column(json_decode($httpClient->getBodies()[1], true, 512, JSON_THROW_ON_ERROR), 'message'),
		);

		$handler->reset();
		$afterReset = $httpClient->getRequests();
		self::assertCount(2, $afterReset);
	}

}

<?php declare(strict_types = 1);

namespace Tests\Orisai\MonologLogtail\Unit;

use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Orisai\MonologLogtail\LogtailClient;
use Orisai\MonologLogtail\LogtailHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Psr18Client;
use Tests\Orisai\MonologLogtail\Unit\Fixtures\CollectingHttpClient;

final class LogtailHandlerTest extends TestCase
{

	public function testLazy(): void
	{
		$httpClient = new Psr18Client();
		$client = new LogtailClient('token', $httpClient, $httpClient, $httpClient);
		$handler = new LogtailHandler($client);

		$handler->close();
		$handler->reset();

		// Just to make phpunit happy
		self::assertTrue(true);
	}

	public function testResetSendsBufferedRecordsOnce(): void
	{
		$httpClient = new CollectingHttpClient();
		$psr17 = new Psr17Factory();
		$handler = new LogtailHandler(new LogtailClient('token', $httpClient, $psr17, $psr17));

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

}

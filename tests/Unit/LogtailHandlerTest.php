<?php declare(strict_types = 1);

namespace Tests\Orisai\MonologLogtail\Unit;

use Monolog\Test\TestCase;
use Orisai\MonologLogtail\LogtailClient;
use Orisai\MonologLogtail\LogtailHandler;
use Symfony\Component\HttpClient\Psr18Client;

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

}

<?php declare(strict_types = 1);

namespace Tests\Orisai\MonologLogtail\Unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\MonologLogtail\LogtailClient;
use PHPUnit\Framework\TestCase;
use Tests\Orisai\MonologLogtail\Unit\Fixtures\QueuedHttpClient;
use Tests\Orisai\MonologLogtail\Unit\Fixtures\TransportFailure;
use Throwable;
use function json_decode;
use const JSON_THROW_ON_ERROR;

final class LogtailClientTest extends TestCase
{

	public function testRequest(): void
	{
		$httpClient = new QueuedHttpClient([202]);
		$client = $this->createClient($httpClient);

		$client->log([['message' => 'one'], ['message' => 'two']]);

		$requests = $httpClient->getRequests();
		self::assertCount(1, $requests);
		$request = $requests[0];
		self::assertSame('POST', $request->getMethod());
		self::assertSame('Bearer token', $request->getHeaderLine('Authorization'));
		self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
		self::assertSame(
			[['message' => 'one'], ['message' => 'two']],
			json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR),
		);
	}

	public function testErrorResponse(): void
	{
		$client = $this->createClient(new QueuedHttpClient([500]));

		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage('Logtail returned an error (500): ');
		$client->log([['message' => 'one']]);
	}

	public function testFailedRequestStartsCooldown(): void
	{
		$httpClient = new QueuedHttpClient([new TransportFailure('down'), 202]);
		$client = $this->createClient($httpClient);

		$this->logExpectingFailure($client, TransportFailure::class);

		$client->log([['message' => 'two']]);

		self::assertCount(1, $httpClient->getRequests());
	}

	public function testErrorResponseStartsCooldown(): void
	{
		$httpClient = new QueuedHttpClient([503, 202]);
		$client = $this->createClient($httpClient);

		$this->logExpectingFailure($client, InvalidArgument::class);

		$client->log([['message' => 'two']]);

		self::assertCount(1, $httpClient->getRequests());
	}

	public function testZeroCooldownRetriesEveryRequest(): void
	{
		$httpClient = new QueuedHttpClient([500, 202, 202]);
		$client = $this->createClient($httpClient);
		$client->setRetryAfter(0);

		$this->logExpectingFailure($client, InvalidArgument::class);

		$client->log([['message' => 'two']]);
		$client->log([['message' => 'three']]);

		self::assertCount(3, $httpClient->getRequests());
	}

	public function testSuccessfulRequestEndsCooldown(): void
	{
		$httpClient = new QueuedHttpClient([500, 202, 500, 202]);
		$client = $this->createClient($httpClient);
		$client->setRetryAfter(0);

		$this->logExpectingFailure($client, InvalidArgument::class);

		$client->log([['message' => 'two']]);
		$client->setRetryAfter(3_600);

		$this->logExpectingFailure($client, InvalidArgument::class);

		$client->log([['message' => 'four']]);

		self::assertCount(3, $httpClient->getRequests());
	}

	/**
	 * @param class-string<Throwable> $exceptionClass
	 */
	private function logExpectingFailure(LogtailClient $client, string $exceptionClass): void
	{
		try {
			$client->log([['message' => 'failing']]);
		} catch (Throwable $exception) {
			self::assertInstanceOf($exceptionClass, $exception);

			return;
		}

		self::fail('Failure was expected.');
	}

	private function createClient(QueuedHttpClient $httpClient): LogtailClient
	{
		$psr17 = new Psr17Factory();

		return new LogtailClient('token', $httpClient, $psr17, $psr17);
	}

}

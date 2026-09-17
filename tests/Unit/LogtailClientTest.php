<?php declare(strict_types = 1);

namespace Tests\Orisai\MonologLogtail\Unit;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\MonologLogtail\LogtailClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Tests\Orisai\MonologLogtail\Unit\Fixtures\ResponseQueue;
use Throwable;
use function json_decode;
use const JSON_THROW_ON_ERROR;

final class LogtailClientTest extends TestCase
{

	private const Url = 'https://s1.example.betterstackdata.com/';

	public function testRequest(): void
	{
		$queue = new ResponseQueue([new MockResponse('', ['http_code' => 202])]);
		$client = $this->createClient($queue);

		$client->log([['message' => 'one'], ['message' => 'two']]);

		$requests = $queue->getRequests();
		self::assertCount(1, $requests);
		$request = $requests[0];
		self::assertSame('POST', $request['method']);
		self::assertSame(self::Url, $request['url']);
		self::assertContains('Authorization: Bearer token', $request['options']['headers']);
		self::assertContains('Content-Type: application/json', $request['options']['headers']);
		self::assertSame(
			[['message' => 'one'], ['message' => 'two']],
			json_decode($queue->getBodies()[0], true, 512, JSON_THROW_ON_ERROR),
		);
	}

	public function testErrorResponse(): void
	{
		$client = $this->createClient(new ResponseQueue([new MockResponse('nope', ['http_code' => 500])]));

		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage('Logtail returned an error (500): nope');
		$client->log([['message' => 'one']]);
	}

	public function testTransportFailure(): void
	{
		$client = $this->createClient(new ResponseQueue([new MockResponse('', ['error' => 'down'])]));

		$this->expectException(TransportExceptionInterface::class);
		$this->expectExceptionMessage('down');
		$client->log([['message' => 'one']]);
	}

	public function testFailedRequestStartsCooldown(): void
	{
		$queue = new ResponseQueue([new MockResponse('', ['error' => 'down'])]);
		$client = $this->createClient($queue);

		$this->logExpectingFailure($client, TransportExceptionInterface::class);
		$client->log([['message' => 'two']]);

		self::assertCount(1, $queue->getRequests());
	}

	public function testErrorResponseStartsCooldown(): void
	{
		$queue = new ResponseQueue([new MockResponse('', ['http_code' => 503])]);
		$client = $this->createClient($queue);

		$this->logExpectingFailure($client, InvalidArgument::class);
		$client->log([['message' => 'two']]);

		self::assertCount(1, $queue->getRequests());
	}

	public function testZeroCooldownRetriesEveryRequest(): void
	{
		$queue = new ResponseQueue([new MockResponse('', ['http_code' => 500])]);
		$client = $this->createClient($queue);
		$client->setRetryAfter(0);

		$this->logExpectingFailure($client, InvalidArgument::class);
		$client->log([['message' => 'two']]);
		$client->log([['message' => 'three']]);

		self::assertCount(3, $queue->getRequests());
	}

	public function testSuccessfulRequestEndsCooldown(): void
	{
		$queue = new ResponseQueue([
			new MockResponse('', ['http_code' => 500]),
			new MockResponse('', ['http_code' => 202]),
			new MockResponse('', ['http_code' => 500]),
		]);
		$client = $this->createClient($queue);
		$client->setRetryAfter(0);

		$this->logExpectingFailure($client, InvalidArgument::class);
		$client->log([['message' => 'two']]);
		$client->setRetryAfter(3_600);

		$this->logExpectingFailure($client, InvalidArgument::class);
		$client->log([['message' => 'four']]);

		self::assertCount(3, $queue->getRequests());
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

	private function createClient(ResponseQueue $queue): LogtailClient
	{
		return new LogtailClient('token', self::Url, new MockHttpClient($queue));
	}

}

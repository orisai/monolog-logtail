<?php declare(strict_types = 1);

namespace Orisai\MonologLogtail;

use Orisai\Exceptions\Logic\InvalidArgument;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function json_encode;
use function time;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class LogtailClient
{

	private const DefaultTimeout = 2;

	private const DefaultMaxDuration = 3;

	private string $token;

	private string $url;

	private HttpClientInterface $client;

	private int $retryAfterSeconds = 60;

	private ?int $failedAt = null;

	public function __construct(string $token, string $url, ?HttpClientInterface $client = null)
	{
		$this->token = $token;
		$this->url = $url;
		$this->client = $client ?? HttpClient::create([
			'timeout' => self::DefaultTimeout,
			'max_duration' => self::DefaultMaxDuration,
		]);
	}

	public function setRetryAfter(int $seconds): void
	{
		$this->retryAfterSeconds = $seconds;
	}

	/**
	 * @param array<mixed>|array<array<mixed>> $data
	 * @throws TransportExceptionInterface
	 */
	public function log(array $data): void
	{
		if ($this->failedAt !== null && time() - $this->failedAt < $this->retryAfterSeconds) {
			return;
		}

		$this->failedAt = time();
		$this->send($data);
		$this->failedAt = null;
	}

	/**
	 * @param array<mixed>|array<array<mixed>> $data
	 * @throws TransportExceptionInterface
	 */
	private function send(array $data): void
	{
		$response = $this->client->request('POST', $this->url, [
			'headers' => [
				'Authorization' => "Bearer $this->token",
				'Content-Type' => 'application/json',
			],
			'body' => json_encode(
				$data,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
			),
		]);

		$code = $response->getStatusCode();
		if ($code >= 400) {
			throw InvalidArgument::create()
				->withMessage("Logtail returned an error ($code): {$response->getContent(false)}");
		}
	}

}

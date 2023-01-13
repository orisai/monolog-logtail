<?php declare(strict_types = 1);

namespace Orisai\MonologLogtail;

use Orisai\Exceptions\Logic\InvalidArgument;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use function json_encode;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class LogtailClient
{

	private string $token;

	private string $url = 'https://in.logtail.com/';

	private ClientInterface $client;

	private RequestFactoryInterface $requestFactory;

	private StreamFactoryInterface $streamFactory;

	public function __construct(
		string $token,
		ClientInterface $client,
		RequestFactoryInterface $requestFactory,
		StreamFactoryInterface $streamFactory
	)
	{
		$this->token = $token;
		$this->client = $client;
		$this->requestFactory = $requestFactory;
		$this->streamFactory = $streamFactory;
	}

	public function setUrl(string $url): void
	{
		$this->url = $url;
	}

	/**
	 * @param array<mixed>|array<array<mixed>> $data
	 * @throws ClientExceptionInterface
	 */
	public function log(array $data): void
	{
		$request = $this->requestFactory->createRequest('POST', $this->url);
		$request = $request
			->withHeader('Authorization', "Bearer $this->token")
			->withHeader('Content-Type', 'application/json')
			->withBody($this->streamFactory->createStream(
				json_encode(
					$data,
					JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
				),
			));

		$response = $this->client->sendRequest($request);

		$code = $response->getStatusCode();
		if ($code >= 400) {
			throw InvalidArgument::create()
				->withMessage("Logtail returned an error ($code): {$response->getBody()->getContents()}");
		}
	}

}

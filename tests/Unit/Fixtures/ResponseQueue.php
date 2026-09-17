<?php declare(strict_types = 1);

namespace Tests\Orisai\MonologLogtail\Unit\Fixtures;

use Symfony\Component\HttpClient\Response\MockResponse;
use function array_shift;

final class ResponseQueue
{

	/** @var list<MockResponse> */
	private array $responses;

	/** @var list<array{method: string, url: string, options: array<string, mixed>}> */
	private array $requests = [];

	/**
	 * @param list<MockResponse> $responses
	 */
	public function __construct(array $responses = [])
	{
		$this->responses = $responses;
	}

	/**
	 * @return list<array{method: string, url: string, options: array<string, mixed>}>
	 */
	public function getRequests(): array
	{
		return $this->requests;
	}

	/**
	 * @return list<string>
	 */
	public function getBodies(): array
	{
		$bodies = [];
		foreach ($this->requests as $request) {
			$bodies[] = (string) $request['options']['body'];
		}

		return $bodies;
	}

	/**
	 * @param array<string, mixed> $options
	 */
	public function __invoke(string $method, string $url, array $options): MockResponse
	{
		$this->requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

		return array_shift($this->responses) ?? new MockResponse('', ['http_code' => 202]);
	}

}

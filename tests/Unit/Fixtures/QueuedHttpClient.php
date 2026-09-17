<?php declare(strict_types = 1);

namespace Tests\Orisai\MonologLogtail\Unit\Fixtures;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function array_shift;

final class QueuedHttpClient implements ClientInterface
{

	/** @var list<int|ClientExceptionInterface> */
	private array $outcomes;

	/** @var list<RequestInterface> */
	private array $requests = [];

	/**
	 * @param list<int|ClientExceptionInterface> $outcomes
	 */
	public function __construct(array $outcomes)
	{
		$this->outcomes = $outcomes;
	}

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$this->requests[] = $request;
		$outcome = array_shift($this->outcomes);

		if ($outcome instanceof ClientExceptionInterface) {
			throw $outcome;
		}

		return new Response($outcome ?? 202);
	}

	/**
	 * @return list<RequestInterface>
	 */
	public function getRequests(): array
	{
		return $this->requests;
	}

}

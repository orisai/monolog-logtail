<?php declare(strict_types = 1);

namespace Tests\Orisai\MonologLogtail\Unit\Fixtures;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class CollectingHttpClient implements ClientInterface
{

	/** @var list<RequestInterface> */
	private array $requests = [];

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$this->requests[] = $request;

		return new Response(202);
	}

	/**
	 * @return list<RequestInterface>
	 */
	public function getRequests(): array
	{
		return $this->requests;
	}

}

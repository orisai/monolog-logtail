<?php declare(strict_types = 1);

namespace Tests\Orisai\MonologLogtail\Unit\Fixtures;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

final class TransportFailure extends RuntimeException implements ClientExceptionInterface
{

}

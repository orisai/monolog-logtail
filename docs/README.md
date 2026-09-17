# Monolog Logtail

[Monolog](https://github.com/Seldaek/monolog) handler for [Logtail](https://betterstack.com/logtail)

## Content

- [Setup](#setup)
- [Basic configuration](#basic-configuration)
- [Sent data](#sent-data)
- [Batches](#batches)
- [Outage handling](#outage-handling)
- [Nette configuration](#nette-configuration)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer require orisai/monolog-logtail
```

Get your source token and ingesting host at betterstack.com -> Telemetry -> Sources -> your source. Every source has
its own ingesting host, there is no default URL.

## Basic configuration

Example uses `symfony/http-client` PSR-18 integration. Install it in order to get example working.

```php
use Monolog\Logger;
use Orisai\MonologLogtail\LogtailClient;
use Orisai\MonologLogtail\LogtailHandler;
use Symfony\Component\HttpClient\Psr18Client;

$logger = new Logger();

$token = '<YOUR_SOURCE_TOKEN>';
$url = 'https://<YOUR_INGESTING_HOST>/';

// Symfony PSR-18 client is just an example, use any PSR-18 client you like
$client = $requestFactory = $streamFactory = new Psr18Client();

$logger->pushHandler(
	new LogtailHandler(
		new LogtailClient($token, $url, $client, $requestFactory, $streamFactory)
	),
);
```

## Sent data

Records are formatted by `Orisai\MonologLogtail\LogtailFormatter` and sent in the shape expected by Better Stack:

```json
{
	"dt": "2023-01-13T12:00:00+00:00",
	"message": "Message logged",
	"level": "Info",
	"level_value": 200,
	"channel": "app",
	"context": {
		"exception": {
			"class": "RuntimeException",
			"message": "Something failed",
			"code": 0,
			"file": "/app/src/Example.php:42",
			"trace": []
		}
	},
	"extra": {}
}
```

Context and extra are normalized - objects are converted to arrays and exceptions are expanded into
`class`, `message`, `code`, `file` and `trace`.

## Batches

Records passed to `LogtailHandler::handle()` are queued in memory and sent in a single request when the handler is
reset or closed (Monolog does that at the end of each request, on `Logger::reset()` and in the destructor).

Records passed to `LogtailHandler::handleBatch()` are sent immediately, together with any queued records. Wrap the
handler in a [`BufferHandler`](https://github.com/Seldaek/monolog/blob/main/src/Monolog/Handler/BufferHandler.php)
with `flushOnOverflow` enabled to send long-running processes' records in bounded batches:

```php
use Monolog\Handler\BufferHandler;
use Monolog\Logger;

$logger->pushHandler(
	new BufferHandler($logtailHandler, 2_000, Logger::DEBUG, true, true),
);
```

## Outage handling

When a request fails (transport error or an error response), the exception is thrown so the failure can be reported,
and `LogtailClient` drops all records for a cooldown period instead of retrying on every following batch. Cooldown
is 60 seconds by default:

```php
$client->setRetryAfter(120);
```

Set `0` to retry with every request.

## Nette configuration

Example configuration for [Nette](https://nette.org) framework

Given example is for [orisai/nette-monolog](https://github.com/orisai/nette-monolog), other integrations should work
similarly.

PSR-18 client must be registered as a service, e.g.
from [orisai/nette-http-client](https://github.com/orisai/nette-http-client).

```neon
orisai.monolog:
	handlers:
		logtail:
			service: Orisai\MonologLogtail\LogtailHandler(
				Orisai\MonologLogtail\LogtailClient(%logtail.token%, %logtail.url%)
			)

parameters:
	logtail:
		token: <YOUR_SOURCE_TOKEN>
		url: https://<YOUR_INGESTING_HOST>/
```

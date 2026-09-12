# Monolog Logtail

[Monolog](https://github.com/Seldaek/monolog) handler for [Logtail](https://betterstack.com/logtail)

## Content

- [Setup](#setup)
- [Basic configuration](#basic-configuration)
- [Sent data](#sent-data)
- [Nette configuration](#nette-configuration)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer require orisai/monolog-logtail
```

Get your API token at logtail.com -> sources -> edit.

## Basic configuration

Example uses `symfony/http-client` PSR-18 integration. Install it in order to get example working.

```php
use Monolog\Logger;
use Orisai\MonologLogtail\LogtailClient;
use Orisai\MonologLogtail\LogtailHandler;
use Symfony\Component\HttpClient\Psr18Client;

$logger = new Logger();

$token = '<YOUR_LOGTAIL_TOKEN>';

// Symfony PSR-18 client is just an example, use any PSR-18 client you like
$client = $requestFactory = $streamFactory = new Psr18Client();

$logger->pushHandler(
	new LogtailHandler(
		new LogtailClient($token, $client, $requestFactory, $streamFactory)
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
				Orisai\MonologLogtail\LogtailClient(%logtail.token%)
			)

parameters:
	logtail:
		token: <YOUR_LOGTAIL_TOKEN>
```

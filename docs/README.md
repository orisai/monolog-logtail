# Monolog Logtail

[Monolog](https://github.com/Seldaek/monolog) handler for [Logtail](https://betterstack.com/logtail)

## Content

- [Setup](#setup)
- [Basic configuration](#basic-configuration)
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

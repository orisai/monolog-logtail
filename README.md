<h1 align="center">
	<img src="https://github.com/orisai/.github/blob/main/images/repo_title.png?raw=true" alt="Orisai"/>
	<br/>
	Monolog Logtail
</h1>

<p align="center">
	<a href="https://github.com/Seldaek/monolog">Monolog</a> handler for <a href="https://betterstack.com/logtail">Logtail</a>
</p>

<p align="center">
	📄 Check out our <a href="docs/README.md">documentation</a>.
</p>

<p align="center">
	💸 If you like Orisai, please <a href="https://orisai.dev/sponsor">make a donation</a>. Thank you!
</p>

<p align="center">
	<a href="https://github.com/orisai/monolog-logtail/actions?query=workflow%3Aci">
		<img src="https://github.com/orisai/monolog-logtail/workflows/ci/badge.svg">
	</a>
	<a href="https://coveralls.io/r/orisai/monolog-logtail">
		<img src="https://badgen.net/coveralls/c/github/orisai/monolog-logtail/v1.x?cache=300">
	</a>
	<a href="https://dashboard.stryker-mutator.io/reports/github.com/orisai/monolog-logtail/v1.x">
		<img src="https://badge.stryker-mutator.io/github.com/orisai/monolog-logtail/v1.x">
	</a>
	<a href="https://packagist.org/packages/orisai/monolog-logtail">
		<img src="https://badgen.net/packagist/dt/orisai/monolog-logtail?cache=3600">
	</a>
	<a href="https://packagist.org/packages/orisai/monolog-logtail">
		<img src="https://badgen.net/packagist/v/orisai/monolog-logtail?cache=3600">
	</a>
	<a href="https://choosealicense.com/licenses/mpl-2.0/">
		<img src="https://badgen.net/badge/license/MPL-2.0/blue?cache=3600">
	</a>
<p>

##

```php
use Monolog\Logger;
use Orisai\MonologLogtail\LogtailClient;
use Orisai\MonologLogtail\LogtailHandler;

$logger = new Logger();
$token = '<YOUR_LOGTAIL_TOKEN>';
$logger->pushHandler(
	new LogtailHandler(
		new LogtailClient($token, /* ... */)
	),
);

$logger->info('Message logged');
```

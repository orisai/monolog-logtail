# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/orisai/monolog-logtail/compare/1.2.0...v1.x)

## [1.2.0](https://github.com/orisai/monolog-logtail/compare/1.1.0...1.2.0) - 2026-09-17

### Added

- `LogtailClient`
	- `setRetryAfter()` - after a failed request, records are dropped for a cooldown (60 seconds by default) instead of retrying with every batch

### Changed

- `LogtailHandler`
	- `handleBatch()` sends the batch immediately, together with any queued records, so records buffered by e.g. `BufferHandler` don't stay in memory until `reset()` or `close()`

## [1.1.0](https://github.com/orisai/monolog-logtail/compare/1.0.2...1.1.0) - 2026-09-17

### Added

- `LogtailFormatter`

### Changed

- `LogtailHandler`
	- Sends records formatted by `LogtailFormatter`, instead of the raw Monolog record
	- `level` is now the level name, `level_value` holds the integer level and `level_name` was removed
	- Context and extra are normalized, so exceptions are expanded

## [1.0.2](https://github.com/orisai/monolog-logtail/compare/1.0.1...1.0.2) - 2024-12-29

### Added

- Composer
	- Allow PHP 8.4

## [1.0.1](https://github.com/orisai/monolog-logtail/compare/1.0.0...1.0.1) - 2024-06-21

### Added

- Composer
	- Allow PHP 8.3
	- Allow psr/http-message:^2.0.0

## [1.0.0](https://github.com/orisai/monolog-logtail/releases/tag/1.0.0) - 2023-01-13

### Added

- `LogtailClient`
- `LogtailHandler`

# [Apk Parser](http://tufanbarisyildirim.github.io/php-apk-parser/)

This package can extract application package files in APK format used by devices running on Android OS. It can open an
APK file and extract the contained manifest file to parse it and retrieve the meta-information it contains like the
application name, description, device feature access permission it requires, etc.. The class can also extract the whole
files contained in the APK file to a given directory.

### Requirements

PHP 8.0+  
PHP 7.3+ is in [2.x.x](https://github.com/tufanbarisyildirim/php-apk-parser/tree/v2.x.x) branch

### Installation

- Install [composer](http://getcomposer.org/download/)
- Run the following command in the folder where `composer.json` is: `composer require tufanbarisyildirim/php-apk-parser`

## Testing

Tests are powered by PHPUnit and can be run fully in Docker.

- Build the PHP test image: `make docker-build`
- Install dependencies in Docker: `make docker-install`
- Run tests in Docker: `make docker-test`
- Run code style checks in Docker (non-mutating): `make docker-lint`
- Run code style auto-fix in Docker: `make docker-format`
- Run PHP syntax/static checks in Docker: `make docker-static`
- Run the full verification pipeline in Docker: `make docker-check`
- Existing aliases are still available: `make test`, `make lint`

## Dependency Lockfile Policy

`composer.lock` is committed in this repository to keep local and CI dependency resolution deterministic.
CI also validates compatibility across dependency ranges (highest and lowest sets).

## Contributing

Fork the repo, make your changes, add your name to developers, and create a pull request with a comment that describe
your changes. That's all!
[Thanks to all contributers](https://github.com/tufanbarisyildirim/php-apk-parser/graphs/contributors)

## Thanks

Thanks JetBrains for the free open source license

<a href="https://www.jetbrains.com/?from=tufanbarisyildirim" target="_blank">
	<img src="https://resources.jetbrains.com/storage/products/company/brand/logos/jb_beam.png" width = "260" align=center  alt="Jetbrains"/>
</a>

### License

Apk Parser is [MIT licensed](./LICENSE.md).

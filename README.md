# [Apk Parser](http://tufanbarisyildirim.github.io/php-apk-parser/)

This package can extract application package files in APK format used by devices running on Android OS.
It can open an APK file and extract the contained manifest file to parse it and retrieve the meta-information
it contains like the application name, description, device feature access permission it requires, etc..
The class can also extract the whole files contained in the APK file to a given directory.

### Requirements

PHP 7.2+

### Installation

- Install [composer](http://getcomposer.org/download/)
- Run the following command in the folder where `composer.json` is: `composer require tufanbarisyildirim/php-apk-parser`

## Testing

Tests are powered by PHPUnit. You have several options.

- Run `phpunit` if PHPUnit is installed globally.
- Install dependencies (requires [Composer](https://getcomposer.org/download)).
  Run `php composer.phar --dev install` or `composer --dev install`. Then `bin/vendor/phpunit` to run version
  installed by Composer. This ensures that you are running a version compatible with the test suite.
  
  
## Contributing

Fork the repo, make your changes, add your name to developers, and create a pull request with a comment that describe your changes. That's all!
[Thanks to all contributers](https://github.com/tufanbarisyildirim/php-apk-parser/graphs/contributors)
  

### License

Apk Parser is [MIT licensed](./LICENSE.md).

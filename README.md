# [Apk Parser](http://tufanbarisyildirim.github.io/php-apk-parser/)

This package can extract application package files in APK format used by devices running on Android OS.
It can open an APK file and extract the contained manifest file to parse it and retrieve the meta-information
it contains like the application name, description, device feature access permission it requires, etc..
The class can also extract the whole files contained in the APK file to a given directory.

### Requirements

PHP 5.3+

### Installation

- Install [composer](http://getcomposer.org/download/)
- Create a composer.json into your project like the following sample:

```json
{
    ...
    "require": {
        "tufanbarisyildirim/php-apk-parser":"dev-master"
    }
}
```

- Then from your `composer.json` folder: `php composer.phar install` or `composer install`

## Testing

Tests are powered by PHPUnit. You have several options.

- Run `phpunit` if PHPUnit is installed globally.
- Install dependencies (requires [Composer](https://getcomposer.org/download)).
  Run `php composer.phar --dev install` or `composer --dev install`. Then `bin/vendor/phpunit` to run version
  installed by Composer. This ensures that you are running a version compatible with the test suite.


### License

Apk Parser is [MIT licensed](./LICENSE.md).
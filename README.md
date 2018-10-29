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
  
  
##Contributing
Fork the repo, make your changes, add your name to developers, and create a pull request with a comment that describe your changes. That's all!
[Thanks to all contributers](https://github.com/tufanbarisyildirim/php-apk-parser/graphs/contributors)
  
##Developers
[Tufan Barış Yıldırım](http://github.com/tufanbarisyildirim)

##Supporters
[MiKandi](https://www.mikandi.com)  Team supports/develops Apk parser

[![Php Storm](https://www.jetbrains.com/phpstorm/documentation/docs/logo_phpstorm.png)](https://www.jetbrains.com/phpstorm)

[JetBrains](https://www.jetbrains.com) provides opensource license to Apk Parser's core developers.

** Add your name here if you want to support/donate apk-parser

## Who Uses Apk Parser
[World #1 Adult App Store MiKandi](http://www.mikandi.com) uses Apk Parser on their app store

[RoidBay Android APK Market](https://www.roidbay.com) uses Apk Parser on their app store

[ApkFiles.com](https://www.apkfiles.com) uses Apk Parser to get data from user apk uploads and fill upload form.

[Add your name here](./BEMENTIONED.md) if you use apk-parser on your any project.

### License

Apk Parser is [MIT licensed](./LICENSE.md).

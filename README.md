# [Apk Parser](http://tufanbarisyildirim.github.io/php-apk-parser/)

This package can extract application package files in APK format used by devices running on Android OS.
It can open an APK file and extract the contained manifest file to parse it and retrieve the meta-information it contains like the application name, description, device feature access permission it requires, etc..
The class can also extract the whole files contained in the APK file to a given directory.


Requirements
PHP 5.3+

if you want to run it on  5.2- see :  [for-php-5.2](https://github.com/tufanbarisyildirim/php-apk-parser/tree/for-php-5.2)

### Installation via composer
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




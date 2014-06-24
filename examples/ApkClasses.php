<?php
include 'autoload.php';

$apk = new ApkParser\Parser('EBHS.apk');

echo '<pre>';
foreach ($apk->getClasses() as $className) {
    echo $className . PHP_EOL;
}

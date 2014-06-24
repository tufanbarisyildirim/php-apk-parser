<?php
include 'autoload.php';

$apk = new ApkParser\Parser('EBHS.apk');

echo '<pre>';
foreach ($apk->getManifest()->getApplication()->getActivityNameList() as $activityName) {
    echo $activityName . PHP_EOL;
}

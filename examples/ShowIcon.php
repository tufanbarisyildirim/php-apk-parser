<?php

include 'autoload.php';

$apk = new \ApkParser\Parser('EBHS.apk');
$icon = $apk->getIcon();

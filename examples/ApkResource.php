<?php

include 'autoload.php';
$apk = new \ApkParser\Parser('EBHS.apk');
$resourceId = $apk->getManifest()->getApplication()->getIcon();
$resources = $apk->getResources($resourceId);

header('Content-type: text/html');
foreach ($resources as $resource) {
    echo '<img src="data:image/png;base64,', base64_encode(stream_get_contents($apk->getStream($resource))), '" />';
}
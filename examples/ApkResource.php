<?php

/**
 * This file is part of the Apk Parser package.
 *
 * (c) Tufan Baris Yildirim <tufanbarisyildirim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include 'autoload.php';
$apk = new \ApkParser\Parser('EBHS.apk');
$resourceId = $apk->getManifest()->getApplication()->getIcon();
$resources = $apk->getResources($resourceId);

$labelResourceId = $apk->getManifest()->getApplication()->getLabel();
$appLabel = $apk->getResources($labelResourceId);
echo $appLabel[0];

header('Content-type: text/html');
echo $appLabel[0] . '<br/>';
foreach ($resources as $resource) {
    echo '<img src="data:image/png;base64,', base64_encode(stream_get_contents($apk->getStream($resource))), '" />';
}
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

header('Content-type: text/html');
foreach ($resources as $resource) {
    echo '<img src="data:image/png;base64,', base64_encode(stream_get_contents($apk->getStream($resource))), '" />';
}
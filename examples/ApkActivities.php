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

$apk = new ApkParser\Parser('EBHS.apk');

echo '<pre>';
foreach ($apk->getManifest()->getApplication()->getActivityNameList() as $activityName) {
    echo $activityName . PHP_EOL;
}

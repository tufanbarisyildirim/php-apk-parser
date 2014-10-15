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
$extractFolder = 'extract_folder';

if (is_dir($extractFolder) || mkdir($extractFolder)) {
    $apk->extractTo($extractFolder);
}
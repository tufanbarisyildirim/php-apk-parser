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

header("Content-Type:text/xml;Charset=UTF-8");
echo $apk->getManifest()->getXmlString();

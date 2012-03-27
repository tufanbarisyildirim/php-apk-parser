<?php
    include '../ApkParser.php';
    $apk = new ApkParser('EBHS.apk');

    header("Content-Type:text/xml;Charset=UTF-8");
    echo $apk->getManifest()->getXmlString();

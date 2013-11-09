<?php
    include 'autoload.php';
    
    $apk = new ApkParser\Parser('EBHS.apk');

    header("Content-Type:text/xml;Charset=UTF-8");
    echo $apk->getManifest()->getXmlString();

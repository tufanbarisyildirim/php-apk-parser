<?php
    include '../ApkParser.php';
    $apk = new ApkParser('EBHS.apk');

    $manifest = $apk->getManifest();
    $xmlObj = $manifest->getXmlObject();

    $permissions = $xmlObj->getPermissions();

    echo '<pre>';
    foreach($permissions as $perm => $description)
    {
        echo $perm . "\t=> " . $description ." \r\n";
    }


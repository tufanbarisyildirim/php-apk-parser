<?php
    include '../ApkParser.php';
    $apk = new ApkParser('EBHS.apk');

    $manifest = $apk->getManifest();
    $obj = $manifest->getXmlObject();

    $permissions = $obj->getPermissions();

    echo '<pre>';
    foreach($permissions as $perm => $description)
    {
        echo $perm . "\t=> " . $description ." \r\n";
    }


<?php
    include '../ApkParser.php';
    $apk = new ApkParser('EBHS.apk');

    $manifest = $apk->getManifest();
    $permissions = $manifest->getPermissions();

    echo '<pre>';
    foreach($permissions as $perm => $description)
    {
        echo $perm . "\t=> " . $description ." \r\n";
    }


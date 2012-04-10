<?php
    include '../ApkParser.php';
    $apk = new ApkParser('EBHS.apk');

    $manifest = $apk->getManifest();
    $permissions = $manifest->getPermissions();

    echo '<pre>';
    echo "Package Name      : " . $manifest->getPackageName()  . "\r\n";
    echo "Vesrion           : " . $manifest->getVersionName()  . " (" . $manifest->getVersionCode() . ")\r\n";
    echo "Min Sdk Level     : " . $manifest->getMinSdkLevel()  . "\r\n";
    echo "Min Sdk Platfrom  : " . $manifest->getMinSdk()->platform['name'] ."\r\n";

    echo "------------- Permssions List -------------\r\n";
    foreach($permissions as $perm => $description)
    {
        echo $perm . "\t=> " . $description ." \r\n";
    }


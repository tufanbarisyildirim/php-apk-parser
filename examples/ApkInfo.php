<?php
    include '../ApkParser.php';
    $apk = new ApkParser('EBHS.apk');

    $manifest = $apk->getManifest();
    $permissions = $manifest->getPermissions();

    echo '<pre>';
    echo "Package Name      : " . $manifest->getPackageName()  . "\r\n";
    echo "Vesrion           : " . $manifest->getVersion()  . "\r\n";
    echo "Min Sdk Level     : ". $manifest->getMinSdkLevel()  . "\r\n";

    $minPlatform = $manifest->getMinSdk();

    echo "Min Sdk Platfrom  :" . $minPlatform->platform['name'] ."\r\n";

    echo "------------- Permssions List -------------\r\n";
    foreach($permissions as $perm => $description)
    {
        echo $perm . "\t=> " . $description ." \r\n";
    }


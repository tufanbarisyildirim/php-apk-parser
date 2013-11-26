<?php
    include 'autoload.php';
    $apk = new \ApkParser\Parser('EBHS.apk');

    $manifest = $apk->getManifest();
    $permissions = $manifest->getPermissions();

    echo '<pre>';
    echo "Package Name      : " . $manifest->getPackageName()  . "\r\n";
    echo "Version           : " . $manifest->getVersionName()  . " (" . $manifest->getVersionCode() . ")\r\n";
    echo "Min Sdk Level     : " . $manifest->getMinSdkLevel()  . "\r\n";
    echo "Min Sdk Platform  : " . $manifest->getMinSdk()->platform ."\r\n";

    echo "------------- Permssions List -------------\r\n";

    // find max length to print more pretty.
    $perm_keys = array_keys($permissions);
    $perm_key_lengths = array_map(function($perm){
        return strlen($perm);
    },$perm_keys);
    $max_length = max($perm_key_lengths);

    foreach($permissions as $perm => $description)
    {
        echo   str_pad($perm,$max_length + 4,' ') . "=> " . $description ." \r\n";
    }

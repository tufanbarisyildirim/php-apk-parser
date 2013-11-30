<?php
    include 'autoload.php';

    $apk = new \ApkParser\Parser('EBHS.apk');
    $extractFolder = 'extract_folder';

    if(is_dir($extractFolder) || mkdir($extractFolder))
    {
        $apk->extractTo($extractFolder);
    }
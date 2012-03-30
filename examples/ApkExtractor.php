<?php
    include '../ApkParser.php';

    $apk = new ApkParser('EBHS.apk');
    $extractFolder = 'extract_folder';

    if(is_dir($extractFolder) || mkdir($extractFolder))
    {
        $apk->extractTo($extractFolder);
}
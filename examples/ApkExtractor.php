<?php
    include '../ApkParser.php';

    $apk = new ApkParser('EBHS.apk');
    $extractFolder = 'extract_folder';

    if(is_dir($extractFolder) || mkdir($extractFolder))
    {
        $apk->getApkArchive()->extractTo($extractFolder);

        //Change the Manifest XML. @see ApkArchive Todo.
        file_put_contents($extractFolder . '/AndroidManifest.xml',$apk->getManifest()->getXmlString());
}
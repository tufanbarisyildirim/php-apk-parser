<?php
    include '../ApkParser.php';

    $apk = new ApkParser('EBHS.apk');

    if(is_dir('extract_folder') || mkdir('extract_folder'))
    {
        $apk->getApkArchive()->extractTo('extract_folder');

        //Change the Manifest XML. @see ApkArchive Todo.
        file_put_contents('extract_folder/AndroidManifest.xml',$apk->getManifest()->getXmlString());
}
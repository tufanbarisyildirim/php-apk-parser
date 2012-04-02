<?php
    function __autoload($className)
    {
        if(is_file($className . ".php"))
            include $className . ".php";
    }

    
   $parser = new AXMLParser(new InputStream('../examples/extract_folder/res/xml/preferences.xml'));
    //$parser = new AXMLParser(new InputStream('../examples/extract_folder/AndroidManifest.xml'));
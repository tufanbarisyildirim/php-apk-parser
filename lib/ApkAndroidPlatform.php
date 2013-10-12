<?php
    // Android Api Version Codes
    define('ANRDOID_API_BASE',1);
    define('ANRDOID_API_BASE_1_1',2);
    define('ANRDOID_API_CUPCAKE',3);
    define('ANRDOID_API_DONUT',4);
    define('ANRDOID_API_ECLAIR',5);
    define('ANRDOID_API_ECLAIR_0_1',6);
    define('ANRDOID_API_ECLAIR_MR1',7);
    define('ANRDOID_API_FROYO',8);
    define('ANRDOID_API_GINGERBREAD',9);
    define('ANRDOID_API_GINGERBREAD_MR1',10);
    define('ANRDOID_API_HONEYCOMB',11);
    define('ANRDOID_API_HONEYCOMB_MR1',12);
    define('ANRDOID_API_HONEYCOMB_MR2',13);
    define('ANRDOID_API_ICE_CREAM_SANDWICH',14);
    define('ANRDOID_API_ICE_CREAM_SANDWICH_MR1',15);
    define('ANRDOID_API_ICE_JELLY_BEAN',16);
    define('ANRDOID_API_ICE_JELLY_BEAN_MR1',17);
    define('ANRDOID_API_ICE_JELLY_BEAN_MR2',18);

    /**
    * @todo : Write comments!
    * 
    * @property $level 
    * @property $versions array
    * @property $url string
    */
    class ApkAndroidPlatform
    {
        private static $platforms = array(
            /**
            * @link http://developer.android.com/guide/topics/manifest/uses-sdk-element.html#ApiLevels
            * @link  http://developer.android.com/about/dashboards/index.html
            */
            0 => array('versions' => array('Undefined'), 'url' => 'Undefined'),
            ANRDOID_API_BASE => array('versions' => array('1.0'), 'url' => 'http://developer.android.com/reference/android/os/Build.VERSION_CODES.html#BASE'),
            ANRDOID_API_BASE_1_1 => array('versions'=>array('1.1'),'url' => 'http://developer.android.com/about/versions/android-1.1.html'),
            ANRDOID_API_CUPCAKE =>  array('versions' => array('1.5'),'url' => 'http://developer.android.com/about/versions/android-1.5.html'),
            ANRDOID_API_DONUT =>  array('versions' => array('1.6'),'url' => 'http://developer.android.com/about/versions/android-1.6.html'),
            ANRDOID_API_ECLAIR => array('versions' => array('2.0'),'url' => 'http://developer.android.com/about/versions/android-2.0.html'),
            ANRDOID_API_ECLAIR_0_1 => array('versions' => array('2.0.1'),'url' => 'http://developer.android.com/about/versions/android-2.0.1.html'),
            ANRDOID_API_ECLAIR_MR1 => array('versions' => array('2.1.x'),'url' => 'http://developer.android.com/about/versions/android-2.1.html'),
            ANRDOID_API_FROYO =>  array('versions' => array('2.2.x'),'url' => 'http://developer.android.com/about/versions/android-2.2.html'),
            ANRDOID_API_GINGERBREAD => array('versions' => array('2.3','2.3.1','2.3.2'),'url' => 'http://developer.android.com/about/versions/android-2.3.html'),
            ANRDOID_API_GINGERBREAD_MR1 => array('versions' => array('2.3.3','2.3.4'),'url' => 'http://developer.android.com/about/versions/android-2.3.3.html'),
            ANRDOID_API_HONEYCOMB => array('versions' => array('3.0.x'),'url' => 'http://developer.android.com/about/versions/android-3.0.html'),
            ANRDOID_API_HONEYCOMB_MR1 => array('versions' => array('3.1.x'),'url' => 'http://developer.android.com/about/versions/android-3.1.html'),
            ANRDOID_API_HONEYCOMB_MR2 => array('versions' => array('3.2'),'url' => 'http://developer.android.com/about/versions/android-3.2.html'),
            ANRDOID_API_ICE_CREAM_SANDWICH => array('versions' => array('4.0','4.0.1','4.0.2'),'url' => 'http://developer.android.com/about/versions/android-4.0.html'),
            ANRDOID_API_ICE_CREAM_SANDWICH_MR1 => array('versions' => array('4.0.3','4.0.4'),'url' => 'http://developer.android.com/about/versions/android-4.0.3.html'),
            ANRDOID_API_ICE_JELLY_BEAN => array('versions' => array('4.1','4.1.1'),'url' => 'http://developer.android.com/about/versions/android-4.1.html'),
            ANRDOID_API_ICE_JELLY_BEAN_MR1 => array('versions' => array('4.2','4.2.2'),'url' => 'http://developer.android.com/about/versions/android-4.2.html'),
            ANRDOID_API_ICE_JELLY_BEAN_MR2 => array('versions' => array('4.3'),'url' => 'http://developer.android.com/about/versions/android-4.3.html'),
        );

        public $level = null;

        /**
        * use a constant with ANDROID_API prefix like ANDROID_API_JELLY_BEAN
        * 
        * @param mixed $apiLevel
        * @return ApkAndroidPlatform
        */

        public function __construct($apiLevel)
        {       
            if(!isset(self::$platforms[$apiLevel]))
                throw new Exception("Unknown Api Level: " . $apiLevel);

            $this->level = $apiLevel;
        }

        public function __get($var)
        {


            switch($var)
            {
                case 'platform':
                    return 'Android ' . implode(',',self::$platforms[$this->level]['versions']);
                    break;
                default:
                    return self::$platforms[$this->level][$var]; 
                    break;    
            } 

        }

}
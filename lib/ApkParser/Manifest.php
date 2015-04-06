<?php
namespace ApkParser;
use ApkParser\AndroidPlatform;

/**
 * This file is part of the Apk Parser package.
 *
 * (c) Tufan Baris Yildirim <tufanbarisyildirim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Manifest extends \ApkParser\Xml
{

    private $xmlParser;
    private $attrs = null;

    /**
     * @param XmlParser $xmlParser
     */
    public function __construct(XmlParser $xmlParser)
    {
        $this->xmlParser = $xmlParser;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->getXmlObject()->getApplication();
    }

    /**
     * Returns ManifestXml as a String.
     * @return string
     */
    public function getXmlString()
    {
        return $this->xmlParser->getXmlString();
    }

    /**
     * Get Application Permissions
     * @return array
     */
    public function getPermissions()
    {
        return $this->getXmlObject()->getPermissions();
    }

    /**
     * Android Package Name
     * @return string
     */
    public function getPackageName()
    {
        return $this->getAttribute('package');
    }

    /**
     * Application Version Name
     * @return string
     */
    public function getVersionName()
    {
        return $this->getAttribute('versionName');
    }

    /**
     * Application Version Code
     * @return mixed
     */
    public function getVersionCode()
    {
        return hexdec($this->getAttribute('versionCode'));
    }

    /**
     * @return bool
     */
    public function isDebuggable()
    {
        return (bool)$this->getAttribute('debuggable');
    }

    /**
     * The minimum API Level required for the application to run.
     * @return int
     */
    public function getMinSdkLevel()
    {
        $xmlObj = $this->getXmlObject();
        $usesSdk = get_object_vars($xmlObj->{'uses-sdk'});
        return hexdec($usesSdk['@attributes']['minSdkVersion']);
    }

    private function getAttribute($attributeName)
    {
        if ($this->attrs === NULL) {
            $xmlObj = $this->getXmlObject();
            $vars = get_object_vars($xmlObj->attributes());
            $this->attrs = $vars['@attributes'];
        }

        if (!isset($this->attrs[$attributeName]))
            throw new \Exception("Attribute not found : " . $attributeName);

        return $this->attrs[$attributeName];
    }

    /**
     * More Information About The minimum API Level required for the application to run.
     * @return AndroidPlatform
     */
    public function getMinSdk()
    {
        return new AndroidPlatform($this->getMinSdkLevel());
    }

    /**
     * get SimleXmlElement created from AndroidManifest.xml
     *
     * @param mixed $className
     * @return \ApkParser\ManifestXmlElement
     */
    public function getXmlObject($className = '\ApkParser\ManifestXmlElement')
    {
        return $this->xmlParser->getXmlObject($className);
    }

    /**
     * Basically string casting method.
     */
    public function __toString()
    {
        return $this->getXmlString();
    }

    /**
     * Android Permissions list
     * @see http://developer.android.com/reference/android/Manifest.permission.html
     *
     * @todo: Move to {lang}_perms.php file, for easy translations.
     * @var mixed
     */
    public static $permissions = array(
        'ACCESS_CHECKIN_PROPERTIES' => 'Allows read/write access to the "properties" table in the checkin database, to change values that get uploaded.',
        'ACCESS_COARSE_LOCATION' => 'Allows an app to access approximate location derived from network location sources such as cell towers and Wi-Fi.',
        'ACCESS_FINE_LOCATION' => 'Allows an app to access precise location from location sources such as GPS, cell towers, and Wi-Fi.',
        'ACCESS_LOCATION_EXTRA_COMMANDS' => 'Allows an application to access extra location provider commands',
        'ACCESS_MOCK_LOCATION' => 'Allows an application to create mock location providers for testing',
        'ACCESS_NETWORK_STATE' => 'Allows applications to access information about networks',
        'ACCESS_SURFACE_FLINGER' => 'Allows an application to use SurfaceFlinger\'s low level features.',
        'ACCESS_WIFI_STATE' => 'Allows applications to access information about Wi-Fi networks',
        'ACCOUNT_MANAGER' => 'Allows applications to call into AccountAuthenticators.',
        'ADD_VOICEMAIL' => 'Allows an application to add voicemails into the system.',
        'AUTHENTICATE_ACCOUNTS' => 'Allows an application to act as an AccountAuthenticator for the AccountManager',
        'BATTERY_STATS' => 'Allows an application to collect battery statistics',
        'BIND_ACCESSIBILITY_SERVICE' => 'Must be required by an AccessibilityService,to ensure that only the system can bind to it.',
        'BIND_APPWIDGET' => 'Allows an application to tell the AppWidget service which application can access AppWidget\'s data.',
        'BIND_DEVICE_ADMIN' => 'Must be required by device administration receiver, to ensure that only the system can interact with it.',
        'BIND_INPUT_METHOD' => 'Must be required by an InputMethodService, to ensure that only the system can bind to it.',
        'BIND_NFC_SERVICE' => 'Must be required by a HostApduService or OffHostApduService to ensure that only the system can bind to it.',
        'BIND_NOTIFICATION_LISTENER_SERVICE' => 'Must be required by an NotificationListenerService, to ensure that only the system can bind to it.',
        'BIND_PRINT_SERVICE' => 'Must be required by a PrintService, to ensure that only the system can bind to it.',
        'BIND_REMOTEVIEWS' => 'Must be required by a RemoteViewsService, to ensure that only the system can bind to it.',
        'BIND_TEXT_SERVICE' => 'Must be required by a TextService (e.g.',
        'BIND_VPN_SERVICE' => 'Must be required by a VpnService, to ensure that only the system can bind to it.',
        'BIND_WALLPAPER' => 'Must be required by a WallpaperService, to ensure that only the system can bind to it.',
        'BLUETOOTH' => 'Allows applications to connect to paired bluetooth devices',
        'BLUETOOTH_ADMIN' => 'Allows applications to discover and pair bluetooth devices ',
        'BLUETOOTH_PRIVILEGED' => 'Allows applications to pair bluetooth devices without user interaction.',
        'BRICK' => 'Required to be able to disable the device (very dangerous!).',
        'BROADCAST_PACKAGE_REMOVED' => 'Allows an application to broadcast a notification that an application package has been removed.',
        'BROADCAST_SMS' => 'Allows an application to broadcast an SMS receipt notification.',
        'BROADCAST_STICKY' => 'Allows an application to broadcast sticky intents.',
        'BROADCAST_WAP_PUSH' => 'Allows an application to broadcast a WAP PUSH receipt notification.',
        'CALL_PHONE' => 'Allows an application to initiate a phone call without going through the Dialer user interface for the user to confirm the call being placed.',
        'CALL_PRIVILEGED' => 'Allows an application to call any phone number, including emergency numbers, without going through the Dialer user interface for the user to confirm the call being placed.',
        'CAMERA' => 'Required to be able to access the camera device.',
        'CAPTURE_AUDIO_OUTPUT' => 'Allows an application to capture audio output.',
        'CAPTURE_SECURE_VIDEO_OUTPUT' => 'Allows an application to capture secure video output.',
        'CAPTURE_VIDEO_OUTPUT' => 'Allows an application to capture video output.',
        'CHANGE_COMPONENT_ENABLED_STATE' => 'Allows an application to change whether an application component (other than its own) is enabled or not.',
        'CHANGE_CONFIGURATION' => 'Allows an application to modify the current configuration, such as locale.',
        'CHANGE_NETWORK_STATE' => 'Allows applications to change network connectivity state',
        'CHANGE_WIFI_MULTICAST_STATE' => 'Allows applications to enter Wi-Fi Multicast mode',
        'CHANGE_WIFI_STATE' => 'Allows applications to change Wi-Fi connectivity state',
        'CLEAR_APP_CACHE' => 'Allows an application to clear the caches of all installed applications on the device.',
        'CLEAR_APP_USER_DATA' => 'Allows an application to clear user data.',
        'CONTROL_LOCATION_UPDATES' => 'Allows enabling/disabling location update notifications from the radio.',
        'DELETE_CACHE_FILES' => 'Allows an application to delete cache files.',
        'DELETE_PACKAGES' => 'Allows an application to delete packages.',
        'DEVICE_POWER' => 'Allows low-level access to power management.',
        'DIAGNOSTIC' => 'Allows applications to RW to diagnostic resources.',
        'DISABLE_KEYGUARD' => 'Allows applications to disable the keyguard',
        'DUMP' => 'Allows an application to retrieve state dump information from system services.',
        'EXPAND_STATUS_BAR' => 'Allows an application to expand or collapse the status bar.',
        'FACTORY_TEST' => 'Run as a manufacturer test application, running as the root user.',
        'FLASHLIGHT' => 'Allows access to the flashlight',
        'FORCE_BACK' => 'Allows an application to force a BACK operation on whatever is the top activity.',
        'GET_ACCOUNTS' => 'Allows access to the list of accounts in the Accounts Service',
        'GET_PACKAGE_SIZE' => 'Allows an application to find out the space used by any package.',
        'GET_TASKS' => 'Allows an application to get information about the currently or recently running tasks.',
        'GET_TOP_ACTIVITY_INFO' => 'Allows an application to retrieve private information about the current top activity, such as any assist context it can provide.',
        'GLOBAL_SEARCH' => 'This permission can be used on content providers to allow the global search system to access their data.',
        'HARDWARE_TEST' => 'Allows access to hardware peripherals.',
        'INJECT_EVENTS' => 'Allows an application to inject user events (keys, touch, trackball) into the event stream and deliver them to ANY window.',
        'INSTALL_LOCATION_PROVIDER' => 'Allows an application to install a location provider into the Location Manager.',
        'INSTALL_PACKAGES' => 'Allows an application to install packages.',
        'INSTALL_SHORTCUT' => 'Allows an application to install a shortcut in Launcher',
        'INTERNAL_SYSTEM_WINDOW' => 'Allows an application to open windows that are for use by parts of the system user interface.',
        'INTERNET' => 'Allows applications to open network sockets.',
        'KILL_BACKGROUND_PROCESSES' => 'Allows an application to call killBackgroundProcesses(String).',
        'LOCATION_HARDWARE' => 'Allows an application to use location features in hardware, such as the geofencing api.',
        'MANAGE_ACCOUNTS' => 'Allows an application to manage the list of accounts in the AccountManager',
        'MANAGE_APP_TOKENS' => 'Allows an application to manage (create, destroy, Z-order) application tokens in the window manager.',
        'MANAGE_DOCUMENTS' => 'Allows an application to manage access to documents, usually as part of a document picker.',
        'MASTER_CLEAR' => 'Not for use by third-party applications.',
        'MEDIA_CONTENT_CONTROL' => 'Allows an application to know what content is playing and control its playback.',
        'MODIFY_AUDIO_SETTINGS' => 'Allows an application to modify global audio settings',
        'MODIFY_PHONE_STATE' => 'Allows modification of the telephony state - power on, mmi, etc.',
        'MOUNT_FORMAT_FILESYSTEMS' => 'Allows formatting file systems for removable storage.',
        'MOUNT_UNMOUNT_FILESYSTEMS' => 'Allows mounting and unmounting file systems for removable storage.',
        'NFC' => 'Allows applications to perform I/O operations over NFC',
        'PERSISTENT_ACTIVITY' => 'This constant was deprecated in API level 9. This functionality will be removed in the future; please do not use. Allow an application to make its activities persistent.',
        'PROCESS_OUTGOING_CALLS' => 'Allows an application to monitor, modify, or abort outgoing calls.',
        'READ_CALENDAR' => 'Allows an application to read the user\'s calendar data.',
        'READ_CALL_LOG' => 'Allows an application to read the user\'s call log.',
        'READ_CONTACTS' => 'Allows an application to read the user\'s contacts data.',
        'READ_EXTERNAL_STORAGE' => 'Allows an application to read from external storage.',
        'READ_FRAME_BUFFER' => 'Allows an application to take screen shots and more generally get access to the frame buffer data.',
        'READ_HISTORY_BOOKMARKS' => 'Allows an application to read (but not write) the user\'s browsing history and bookmarks.',
        'READ_INPUT_STATE' => 'This constant was deprecated in API level 16. The API that used this permission has been removed.',
        'READ_LOGS' => 'Allows an application to read the low-level system log files.',
        'READ_PHONE_STATE' => 'Allows read only access to phone state.',
        'READ_PROFILE' => 'Allows an application to read the user\'s personal profile data.',
        'READ_SMS' => 'Allows an application to read SMS messages.',
        'READ_SOCIAL_STREAM' => 'Allows an application to read from the user\'s social stream.',
        'READ_SYNC_SETTINGS' => 'Allows applications to read the sync settings',
        'READ_SYNC_STATS' => 'Allows applications to read the sync stats',
        'READ_USER_DICTIONARY' => 'Allows an application to read the user dictionary.',
        'REBOOT' => 'Required to be able to reboot the device.',
        'RECEIVE_BOOT_COMPLETED' => 'Allows an application to receive the ACTION_BOOT_COMPLETED that is broadcast after the system finishes booting.',
        'RECEIVE_MMS' => 'Allows an application to monitor incoming MMS messages, to record or perform processing on them.',
        'RECEIVE_SMS' => 'Allows an application to monitor incoming SMS messages, to record or perform processing on them.',
        'RECEIVE_WAP_PUSH' => 'Allows an application to monitor incoming WAP push messages.',
        'RECORD_AUDIO' => 'Allows an application to record audio',
        'REORDER_TASKS' => 'Allows an application to change the Z-order of tasks',
        'RESTART_PACKAGES' => 'This constant was deprecated in API level 8. The restartPackage(String) API is no longer supported.',
        'SEND_RESPOND_VIA_MESSAGE' => 'Allows an application (Phone) to send a request to other applications to handle the respond-via-message action during incoming calls.',
        'SEND_SMS' => 'Allows an application to send SMS messages.',
        'SET_ACTIVITY_WATCHER' => 'Allows an application to watch and control how activities are started globally in the system.',
        'SET_ALARM' => 'Allows an application to broadcast an Intent to set an alarm for the user.',
        'SET_ALWAYS_FINISH' => 'Allows an application to control whether activities are immediately finished when put in the background.',
        'SET_ANIMATION_SCALE' => 'Modify the global animation scaling factor.',
        'SET_DEBUG_APP' => 'Configure an application for debugging.',
        'SET_ORIENTATION' => 'Allows low-level access to setting the orientation (actually rotation) of the screen.',
        'SET_POINTER_SPEED' => 'Allows low-level access to setting the pointer speed.',
        'SET_PREFERRED_APPLICATIONS' => 'This constant was deprecated in API level 7. No longer useful, see addPackageToPreferred(String) for details.',
        'SET_PROCESS_LIMIT' => 'Allows an application to set the maximum number of (not needed) application processes that can be running.',
        'SET_TIME' => 'Allows applications to set the system time.',
        'SET_TIME_ZONE' => 'Allows applications to set the system time zone ',
        'SET_WALLPAPER' => 'Allows applications to set the wallpaper',
        'SET_WALLPAPER_HINTS' => 'Allows applications to set the wallpaper hints',
        'SIGNAL_PERSISTENT_PROCESSES' => 'Allow an application to request that a signal be sent to all persistent processes.',
        'STATUS_BAR' => 'Allows an application to open, close, or disable the status bar and its icons.',
        'SUBSCRIBED_FEEDS_READ' => 'Allows an application to allow access the subscribed feeds ContentProvider.',
        'SUBSCRIBED_FEEDS_WRITE' => '',
        'SYSTEM_ALERT_WINDOW' => 'Allows an application to open windows using the type TYPE_SYSTEM_ALERT, shown on top of all other applications.',
        'TRANSMIT_IR' => 'Allows using the device\'s IR transmitter, if available',
        'UNINSTALL_SHORTCUT' => 'Allows an application to uninstall a shortcut in Launcher',
        'UPDATE_DEVICE_STATS' => 'Allows an application to update device statistics.',
        'USE_CREDENTIALS' => 'Allows an application to request authtokens from the AccountManager',
        'USE_SIP' => 'Allows an application to use SIP service',
        'VIBRATE' => 'Allows access to the vibrator',
        'WAKE_LOCK' => 'Allows using PowerManager WakeLocks to keep processor from sleeping or screen from dimming',
        'WRITE_APN_SETTINGS' => 'Allows applications to write the apn settings.',
        'WRITE_CALENDAR' => 'Allows an application to write (but not read) the user\'s calendar data.',
        'WRITE_CALL_LOG' => 'Allows an application to write (but not read) the user\'s contacts data.',
        'WRITE_CONTACTS' => 'Allows an application to write (but not read) the user\'s contacts data.',
        'WRITE_EXTERNAL_STORAGE' => 'Allows an application to write to external storage.',
        'WRITE_GSERVICES' => 'Allows an application to modify the Google service map.',
        'WRITE_HISTORY_BOOKMARKS' => 'Allows an application to write (but not read) the user\'s browsing history and bookmarks.',
        'WRITE_PROFILE' => 'Allows an application to write (but not read) the user\'s personal profile data.',
        'WRITE_SECURE_SETTINGS' => 'Allows an application to read or write the secure system settings.',
        'WRITE_SETTINGS' => 'Allows an application to read or write the system settings.',
        'WRITE_SMS' => 'Allows an application to write SMS messages.',
        'WRITE_SOCIAL_STREAM' => 'Allows an application to write (but not read) the user\'s social stream data.',
        'WRITE_SYNC_SETTINGS' => 'Allows applications to write the sync settings',
        'WRITE_USER_DICTIONARY' => 'Allows an application to write to the user dictionary.'
    );

    public static $permission_flags = array(
        'ACCESS_CHECKIN_PROPERTIES' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'ACCESS_COARSE_LOCATION' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'ACCESS_FINE_LOCATION' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'ACCESS_LOCATION_EXTRA_COMMANDS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'ACCESS_MOCK_LOCATION' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'ACCESS_NETWORK_STATE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'ACCESS_SURFACE_FLINGER' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'ACCESS_WIFI_STATE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'ACCOUNT_MANAGER' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'ADD_VOICEMAIL' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'AUTHENTICATE_ACCOUNTS' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'BATTERY_STATS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BIND_ACCESSIBILITY_SERVICE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BIND_APPWIDGET' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BIND_DEVICE_ADMIN' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BIND_INPUT_METHOD' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BIND_NFC_SERVICE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BIND_NOTIFICATION_LISTENER_SERVICE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BIND_PRINT_SERVICE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BIND_REMOTEVIEWS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BIND_TEXT_SERVICE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BIND_VPN_SERVICE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BIND_WALLPAPER' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BLUETOOTH' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'BLUETOOTH_ADMIN' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'BLUETOOTH_PRIVILEGED' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'BRICK' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'BROADCAST_PACKAGE_REMOVED' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BROADCAST_SMS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BROADCAST_STICKY' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'BROADCAST_WAP_PUSH' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CALL_PHONE' =>
            array(
                'cost' => true,
                'warning' => true,
                'danger' => false,
            ),
        'CALL_PRIVILEGED' =>
            array(
                'cost' => true,
                'warning' => true,
                'danger' => true,
            ),
        'CAMERA' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CAPTURE_AUDIO_OUTPUT' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CAPTURE_SECURE_VIDEO_OUTPUT' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CAPTURE_VIDEO_OUTPUT' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CHANGE_COMPONENT_ENABLED_STATE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CHANGE_CONFIGURATION' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CHANGE_NETWORK_STATE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CHANGE_WIFI_MULTICAST_STATE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CHANGE_WIFI_STATE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CLEAR_APP_CACHE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CLEAR_APP_USER_DATA' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'CONTROL_LOCATION_UPDATES' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'DELETE_CACHE_FILES' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'DELETE_PACKAGES' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'DEVICE_POWER' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'DIAGNOSTIC' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'DISABLE_KEYGUARD' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'DUMP' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'EXPAND_STATUS_BAR' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'FACTORY_TEST' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'FLASHLIGHT' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'FORCE_BACK' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'GET_ACCOUNTS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'GET_PACKAGE_SIZE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'GET_TASKS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'GET_TOP_ACTIVITY_INFO' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'GLOBAL_SEARCH' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'HARDWARE_TEST' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'INJECT_EVENTS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'INSTALL_LOCATION_PROVIDER' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'INSTALL_PACKAGES' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'INSTALL_SHORTCUT' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'INTERNAL_SYSTEM_WINDOW' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'INTERNET' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'KILL_BACKGROUND_PROCESSES' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => true,
            ),
        'LOCATION_HARDWARE' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'MANAGE_ACCOUNTS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'MANAGE_APP_TOKENS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'MANAGE_DOCUMENTS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'MASTER_CLEAR' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => true,
            ),
        'MEDIA_CONTENT_CONTROL' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'MODIFY_AUDIO_SETTINGS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'MODIFY_PHONE_STATE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'MOUNT_FORMAT_FILESYSTEMS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'MOUNT_UNMOUNT_FILESYSTEMS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'NFC' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'PERSISTENT_ACTIVITY' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'PROCESS_OUTGOING_CALLS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_CALENDAR' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_CALL_LOG' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_CONTACTS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_EXTERNAL_STORAGE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_FRAME_BUFFER' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_HISTORY_BOOKMARKS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_INPUT_STATE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => true,
            ),
        'READ_LOGS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_PHONE_STATE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_PROFILE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_SMS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_SOCIAL_STREAM' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_SYNC_SETTINGS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_SYNC_STATS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'READ_USER_DICTIONARY' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'REBOOT' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'RECEIVE_BOOT_COMPLETED' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'RECEIVE_MMS' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'RECEIVE_SMS' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'RECEIVE_WAP_PUSH' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'RECORD_AUDIO' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'REORDER_TASKS' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'RESTART_PACKAGES' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'SEND_RESPOND_VIA_MESSAGE' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'SEND_SMS' =>
            array(
                'cost' => true,
                'warning' => true,
                'danger' => false,
            ),
        'SET_ACTIVITY_WATCHER' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => true,
            ),
        'SET_ALARM' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'SET_ALWAYS_FINISH' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'SET_ANIMATION_SCALE' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'SET_DEBUG_APP' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'SET_ORIENTATION' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'SET_POINTER_SPEED' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => true,
            ),
        'SET_PREFERRED_APPLICATIONS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => true,
            ),
        'SET_PROCESS_LIMIT' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'SET_TIME' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'SET_TIME_ZONE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'SET_WALLPAPER' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'SET_WALLPAPER_HINTS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'SIGNAL_PERSISTENT_PROCESSES' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'STATUS_BAR' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'SUBSCRIBED_FEEDS_READ' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'SUBSCRIBED_FEEDS_WRITE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'SYSTEM_ALERT_WINDOW' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'TRANSMIT_IR' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'UNINSTALL_SHORTCUT' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'UPDATE_DEVICE_STATS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'USE_CREDENTIALS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'USE_SIP' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'VIBRATE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'WAKE_LOCK' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'WRITE_APN_SETTINGS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => true,
            ),
        'WRITE_CALENDAR' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'WRITE_CALL_LOG' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => true,
            ),
        'WRITE_CONTACTS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => true,
            ),
        'WRITE_EXTERNAL_STORAGE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'WRITE_GSERVICES' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => true,
            ),
        'WRITE_HISTORY_BOOKMARKS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => true,
            ),
        'WRITE_PROFILE' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => true,
            ),
        'WRITE_SECURE_SETTINGS' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'WRITE_SETTINGS' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'WRITE_SMS' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'WRITE_SOCIAL_STREAM' =>
            array(
                'cost' => false,
                'warning' => true,
                'danger' => false,
            ),
        'WRITE_SYNC_SETTINGS' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
        'WRITE_USER_DICTIONARY' =>
            array(
                'cost' => false,
                'warning' => false,
                'danger' => false,
            ),
    );
}
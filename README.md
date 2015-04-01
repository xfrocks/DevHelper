# XenForo Developer Helper add-on

## Installation
### The Easy Way
Just copy files and import the xml file as usual.

If you want to help developing this add-on. It's recommended to clone the repo elsewhere then create symbolic links for `js/DevHelper` and `library/DevHelper`.
### Advanced Installation
Edit XenForo `index.php` and `admin.php` files in root directory. Look for these lines:

```
require($fileDir . '/library/XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($fileDir . '/library');
```

Replace them with:

```
require($fileDir . '/library/XenForo/Autoloader.php');
require($fileDir . '/library/DevHelper/Autoloader.php');
DevHelper_Autoloader::getDevHelperInstance()->setupAutoloader($fileDir . '/library');
```
This is required to use features like ShippableHelper injection, debug information in AJAX requests, etc.

## Features
 * Quickly setup add-on environment (UNIX only) with command line `xf-new-addon addOnId path/to/xenforo/root`. This will create a new directory `addOnId` in the current directory with the below layout. All inner `addOnId` directories will be symlink'd to their places within `path/to/xenforo/root` making it easy for you to start coding. All files should be put within one of these 3 directories.

```
addOnId
    |
    |--repo
        |
        |--js
            |
            |--addOnId
        |--library
            |
            |--addOnId
        |--styles
            |
            |--default
                |
                |--addOnId
```

 * __Data Manager__ with flexible configuration. It can generates DataWriter, Model, Route PrefixAdmin, ControllerAdmin for you. See __Configuration__ for detailed information. 
 * __Generate Installer___ (and uninstaller too) using data from __Data Manager__.
 * __File Export__ to copy clean files from working directory to another directory for easy package. Added benefits include:
     * Auto generated `FileSums.php`
     * Phrase check (unused phrases, deleted phrases, phrases from other add-ons)
     * Auto generated `XFCP` classes for better IDE support (class hint, method hint, etc.)
 *. Template editors

## Configuration
The first time you visit __Data Manager__ for an add-on, a new file will be generated in `library/addOnId/DevHelper/Config.php` and it looks like this:

```
<?php

class addOnId_DevHelper_Config extends DevHelper_Config_Base
{
    protected function _upgrade()
    {
        return true; // remove this line to trigger update
    }
}
```

When it's time to `addDataClass` or `addDataPatch`, you write those statement inside the `_upgrade()` method and visit __Data Manager__ to trigger the upgrade. For example, if you need a `money` field in `xf_user` table, I will write this:

```
<?php

class addOnId_DevHelper_Config extends DevHelper_Config_Base
{
    protected function _upgrade()
    {
        $this->addDataPatch(
            'xf_user',
            array(
                'name' => 'money',
                'type' => 'uint',
                'default' => 0,
            )
        );
    }
}
```

When the `Config` file is upgraded, it will look like this:

```
<?php

class addOnId_DevHelper_Config extends DevHelper_Config_Base
{
    protected $_dataPatches = array(
        'xf_user' => array(
            'addonid_money' => array('name' => 'addonid_money', 'type' => 'uint', 'default' => 0),
        ),
    );

    protected function _upgrade()
    {
        return true; // remove this line to trigger update
    }
}
```

And if you generate installer from the AdminCP, you will get this `Installer` file:

```
<?php

class addOnId_Installer
{
    protected static $_patches = array(
        array(
            'table' => 'xf_user',
            'field' => 'addonid_money',
            'showTablesQuery' => 'SHOW TABLES LIKE \'xf_user\'',
            'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_user` LIKE \'addonid_money\'',
            'alterTableAddColumnQuery' => 'ALTER TABLE `xf_user` ADD COLUMN `addonid_money` INT(10) UNSIGNED DEFAULT \'0\'',
            'alterTableDropColumnQuery' => 'ALTER TABLE `xf_user` DROP COLUMN `addonid_money`',
        ),
    );

    public static function install($existingAddOn, $addOnData)
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['showTablesQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['showColumnsQuery']);
            if (empty($existed)) {
                $db->query($patch['alterTableAddColumnQuery']);
            }
        }
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['showTablesQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['showColumnsQuery']);
            if (!empty($existed)) {
                $db->query($patch['alterTableDropColumnQuery']);
            }
        }
    }
}
```
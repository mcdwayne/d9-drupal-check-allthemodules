Copyright 2018 Juan Martinez (https://makers365.com)

Description
-----------
This module provides integration with the free HTTP Web Push service from
OneSignal allowing visitors to subscribe for notifications and site owners to
send notifications to the subscribers.


Prerequisites
-------------
You must signup for a OneSignal account and obtain an App ID and download
some files at https://onesignal.com.

Once you have created an account at OneSignal.com:

* Add a new app
* On the "Select one platform" screen, chose "Web Push"
* On the "Chose Integration" screen, chose "Custom Code"
* Enter your site name, URL and Icon URL.
* Click "Save"

You will be given a link to download OneSignal SDK files. Download and extract
these files, you will be using them as we explain below in the installation.

You will be given also a code to add to the `<head>` of your site; however
you DON'T need the full code, all you need from this code is your `appId`.


Installation
------------
In Drupal 8 you can install some modules without composer; although it is
recommended to use composer.

**Installing Without Composer**

Copy the onsignal module directory and all its contents to your contributed
modules directory.

**Installing Using Composer**

Run the following command:
 
 `composer require drupal/onesignal`
 
Whether you are using composer or not, to complete the installation, you need
to upload the following files provided by OneSignal to the root of your
website:

* manifest.json
* OneSignalSDKUpdaterWorker.js
* OneSignalSDKWorker.js

Note: We described above how to obtain these files and your `appId`.

To enable this module, go to:

Administration -> Extend
 
...and enable OneSignal.

Configuration
-------------
This module defines the permission: Administer OneSignal settings. Users
should be granted this permission before they can make changes to the
configurations of this module.

To configure this module, go to:
 
Configuration > Web services > OneSignal

Provide the required parameters as instructed on that page.

For site builders
-----------------

While building your site, you may need different configuartions for your
local, stage and live environments. If so, you can override the
configurations in your settings.local.php or settings.php files by adding the
following code:

```
/**
 * Onesignal local configurations override.
 */
$config['onesignal.config'] = [
  'onesignal_app_id' => '11111111111111111',
  'onesignal_safari_web_id' => 'web.onesignal.auto.2222222222222222',
];

```

API
---
Not yet available in the Drupal 8 version. Patches and code to help
implementing the API are welcome.

Bugs/Features/Patches
---------------------
If you want to report bugs, feature requests, or submit a patch, please do
so at the project page on the Drupal web site.
http://drupal.org/project/onesignal


Author of the Drupal 8 version
------------------------------
Juan Martinez (https://makers365.com)

If you use this module, find it useful, and want to send the author a thank
you note, then use the Contact page at the URLs above.

The author can also be contacted for paid customizations of this and other
modules.

Introduction:
-------------------
This Module provide functionality to delete MaxCDN cache from Drupal GUI.
There are not need to go MaxCDN dashboard and clear cache from control panel.
You can do it by the help of this module. 

Requirements:
------------------
This module requires the following modules:
* Composer Manager
PHP version and Extention:
* PHP 5.3 or above
* PHP Curl Extension

Installation:
------------
 * Install MaxCDN Library from composer (use Composer Manager module)
 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.

How To Use:
-----------------
1. Create API on MaxCDN Account
    a. Goto https://cp.maxcdn.com/account/api
    b. Click "Create Application"
    c. Enter Name, Description, Application URL.
    d. Select Permission.
    d. Save.

2. Go to Configuration - Development - MaxCDN Configuration.
3. Enter Company Alias, Consumer Key, Consumer Secret 
   (get these details from MaxCDN API dashboard 
   https://cp.maxcdn.com/account/api ).
4. Click on Save Configuration.
5. Select Zone from list.
6. Click on Clear cache.

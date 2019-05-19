--------------------------------------------------------------------------------
                       Yandex.Disk (API & StreamWrapper)
--------------------------------------------------------------------------------

An implementation of a WebDAV API of the Yandex.Disk cloud storage service
and stream wrapper class to work with users Disks via filesystem functions.

--------------------------------------------------------------------------------
INTRODUCTION
--------------------------------------------------------------------------------

This module integrates your Drupal site with Yandex.Disk storage service
(https://disk.yandex.com). Other modules and site administrators or even usual
users may use uri like the following in place of any paths after authentication
with their Yandex accounts:
 * yandexdisk://yandex_username/path_to_the_file
 * yandexdisk://yandex_username/path_to_the_directory

For a full description of the module, visit the project page:
 * https://www.drupal.org/project/yandexdisk
To submit bug reports and feature suggestions, or to track changes:
 * https://www.drupal.org/project/issues/yandexdisk

--------------------------------------------------------------------------------
INSTALLATION AND REQUIREMENTS
--------------------------------------------------------------------------------

Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.

The only requirement is another module which can authenticate site users or at
least a maintenance account (admin) with their Yandex.Disk accounts:
 * Yandex OAuth [https://www.drupal.org/project/yandex_oauth]

--------------------------------------------------------------------------------
CONFIGURATION
--------------------------------------------------------------------------------

As a site administrator configure the module mentioned above to use Yandex open
authentication. Configure your Yandex app at https://oauth.yandex.com to use a
scope/permission 'Application access to Yandex.Disk' in 'Yandex.Disk WebDAV API'
section.

Authenticate yourself using that module with Yandex and/or let your users do
that with their Yandex accounts. This way all access tokens are stored in
database for later use with this module.

Then add some view/edit permissions to roles that need access to the service.

CAUTION: USE INTELLIGENTLY. BE CAREFUL OF THAT SOME FUNCTIONALITY MAY DELETE,
         OVERWRITE OR EXPOSE YOUR USERS DISKS DATA.

Now you may use authenticated yandexdisk streams instead of any local path or
any URL or work with them as you like.

--------------------------------------------------------------------------------
INFORMATION FOR DEVELOPERS
--------------------------------------------------------------------------------

If you're going to write your own module on the base of this one, you may find
these methods and functions useful:
 * YandexDiskManager::getDisk():
   Creates an object to work with one user's storage (a Disk). The argument of
   this function is a Yandex username. Then you can use any public method of the
   returned object. Main of them I guess would be imagePreview(), publish(),
   unpublish() and quota(). Because other functionality exists in stream wrapper
   class.
 * yandexdisk_access():
   Use this to check if some site user has a privilege to do some operation on
   some yandexdisk:// uri. You can also implement hook_yandexdisk_access() in
   your module to modify default behavior.

--------------------------------------------------------------------------------
LIMITATIONS
--------------------------------------------------------------------------------

Current implementation of the module (specifically the StreamWrapper) does not
allow to use Yandex.Disk as a default site storage (instead of public/private or
another). This is because of the username part in the uri.

--------------------------------------------------------------------------------
PROJECT MAINTAINER
--------------------------------------------------------------------------------

 * Mike Shiyan [https://www.drupal.org/u/pingwin4eg]

Source Code Editor 8.x-1.x
--------------------------

### About this Module

The SC Editor module is an online source code editor for development purposes. One can quickly edit code on server and revert back once done.

The primary use case for this module is to:

- **Debug** an issue quickly on prod, which cannot be recreated on local for some reason.
- **View** the entire source code in the configs.

### Goals

- A full fledged online IDE for editing source code of the website.

### Installing the SC Editor Module

1. Download the module into your modules/contrib folder.

2. This patch has to be applied for this module to work : https://www.drupal.org/files/issues/2019-01-12/drupal_core-php-ssh2-check-verification-3022646-6-D8.patch

3. One must have FTP access. i.e., a username and password of a user that has FTP access.

    3. 1. You can also connect using ssh key authnetication. Put the path to your ssh key in your settings.php file or fill the advanced section in `/admin/config/sceditor/ftpaccess`
    3. 2. Settings file config : 
        ```
        $settings['sceditor.settings']['private_key'] = '/path/to/private/key/file';
        $settings['sceditor.settings']['public_key'] = '/path/to/public/key/file';
        $settings['sceditor.settings']['ssh_secret'] = 'passphrase'; //Leave empty if no passphrase.
        ```
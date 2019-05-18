CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Similar modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------

Protected file module provide a new field type which extends File field and
permit to enable/disable for each file the possibility to prevent users to
download the file, if they don't have the right permission.

This module is useful if you want to prevent users to donwload file, but you
want display the file regardless.

REQUIREMENTS
------------

This module provide a field type bases on the File field. File is provided by
Drupal core.

This module requires that the private system file is available.


SIMILAR MODULES
-------------------

Field permissions permit to set permissions on fields, and then can prevent
users to access to file uploaded with a File field. But users without the
permissions don't see then the file attached to an entity.

Protected file permit to display every files, but prevent users to download
them. This can be useful if you want that users have to register to be able
to download some (specific) file(s).


INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.


CONFIGURATION
-------------

- Create a "Protected file" field on an entity
- Configure the field settings as the standard File field (directory, extension, etc.)
- Configure the field formatter : you can specify if the file is downloaded in
a new tab, the redirect path for users without permissions, if the redirect path
is open inside a modal or not, and the text used for the title link
- Create content and upload some files. For each file you can protect them

You can override the twig template protected-file-link.html.twig if you want
customize the feel and look of your protected files.

By default, the module provide a markup based on bootstrap class for protected
files. This markup is available in the {{ icon }} twig variable. You can easily
override it in the template or in a preprocess fonction.

TROUBLESHOOTING
---------------


FAQ
---


MAINTAINERS
-----------

Current maintainers:
 * flocondetoile - https://drupal.org/u/flocondetoile

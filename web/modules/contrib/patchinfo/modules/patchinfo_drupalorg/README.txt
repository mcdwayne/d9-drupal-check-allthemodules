CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration


INTRODUCTION
------------

The PatchInfo Drupal.org module allows you to add Drupal.org information about
patches in the patchinfo-list drush command.


REQUIREMENTS
------------

This module does depend on the Patchinfo and Drush modules.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * Add information about a patch using info.yml patch source

   In the *.info.yml file of a patched theme or module, add a new list like the
   one shown below:

   patches:
     - 'https://www.drupal.org/node/1739718 Issue 1739718, Patch #32'

   You can add multiple entries to the list. Each entry should start with the
   URL of the issue or patch followed by any kind of information about the
   patch. The URL is optional.

   You can use any URL or description, that is convenient to you.

   If you are patching a submodule, you may add the patch entry to the
   *.info.yml file of the submodule.

 * Add information about a patch using composer.json patch source

   The composer.json patch source assumes, that 'cweagans/composer-patches' is
   used for patch management. See https://github.com/cweagans/composer-patches
   for more information.

   For Drupal Core, it will check for a composer.json in your Drupal root
   directory or in the 'core' folder.

   Presently, the source plugin will skip any patches for packages outside the
   'drupal/' namespace.

 * Add the drupal.org issue number to the description of the patch

   Add the issue number preferably at the front of the description. In case of
   the yml file directly after the patch url.

   Accepted are the following notations:

    * Issue 123456
    * issue 123456
    * #123456

   Followed by . or : or | or , or space or EOL.

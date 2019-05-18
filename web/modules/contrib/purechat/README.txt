// $Id: README.txt,v 1.0 2014/02/24 16:45:15 rajeshrhino Exp $

### ABOUT

  This module adds the necessary script to the footer of ones site for prompting users to chat via purechat.

  Current Features:
      * Administration settings to allow setting your account number for the script
      * Setting the pages in which to show the script:
            o From a blacklist of pages
            o From a whitelist of pages
            o By returning a value of true or false from PHP snippet
      * Setting visibility of script by role

### INSTALLING

  1. Extract purechat tarball into your sites/all/modules or modules directory so it looks like sites/all/modules/purechat or modules/purechat
  2. Navigate to Extend and enable the module.
  3. Navigate to Configuration -> System -> Purechat and add your account number as well as any other configuration options you want. NOTE: For using PHP code for visibility settings, you need to manually install PHP Filter module, as this module is removed from drupal core.

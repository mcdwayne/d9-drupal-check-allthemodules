CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers

INTRODUCTION
------------
Fancy select is a jQuery plug-in developed by
Octopus Creative (http://octopuscreative.com/).

This module loads fancySelect (http://code.octopuscreative.com/fancyselect/)
jQuery plug-in, which converts simple HTML select DOM elements with a specified
CSS class into a stylish select box. You may configure DOM CSS from settings
form.

REQUIREMENTS
------------
This module requires the following modules:
 * Libraries API (https://www.drupal.org/project/libraries)
   This module work with jquery >= 1.7. After installing jQuery Update don't
   forget to upgrade default jquery version. 

INSTALLATION
-------------
* Download the latest fancySelect javascript library from 
  https://github.com/octopuscreative/FancySelect, extract the content and place
  the entire directory inside any of the following directories:
 
  -  libraries
       Recommended. This is Drupal's standard third party library
       install location. Create a folder if not available.
  -  sites/all/libraries
       Recommended. This is Drupal's standard third party library
       install location.
  -  profiles/{profilename}/libraries
       Recommended if this library is to be profile specific.

  After install, you should have something like this:
    libraries/fancyselect/...
   
  WARNING!
  --------
  Library location is determined by file scan. This scan is performed only once
  and the location is cached. If the library location is changed for any reason
  (such as after upgrading the js lib), visit admin/reports/status to force a
  re-scan and make sure the "fancySelect library" status
  entry shows "Installed".
        
* Install as you would normally install a contributed Drupal module. See:
  https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
  for further information.
 
CONFIGURATION
-------------
Go to admin/config/user-interface/fancyselect to configure.

  * Insert value for 'fancySelect DOM selector' same as jQuery selector
    you want to convert into fancy select and save the form. 

TROUBLESHOOTING
---------------
  * If the module not getting installed even after resolving all dependencies
    
    - Check libraries/fancyselect is accessible and 
      have read access
 
MAINTAINERS
-----------
Current maintainers:
  * Dileep K. Mishra (mishradileep) - https://www.drupal.org/u/mishradileep
  * Bhanu Prakash (bhanuji) - https://www.drupal.org/u/bhanuji
  * Jagadeesh Javvadi (jagadeeshramuj) - https://www.drupal.org/u/jagadeeshramuj

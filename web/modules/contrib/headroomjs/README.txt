Headroom.js for Drupal 8
========================

Installation
============

1. Download the Headroom.js zip from GitHub: https://github.com/WickyNilliams/headroom.js
2. Unzip into /libraries in your docroot, rename the folder headroomjs
3. Download https://unpkg.com/headroom.js and put it at /libraries/headroomjs/headroom.js
3. Enable Headroom.js module using the modules page or Drush. (A warning message will appear if files are not located)

Configuration
=============

Configure any necessary settings at /admin/config/user-interface/headroomjs . The first box must be checked to enable
headroom.js functionality at the top (it is disabled by default).

Important: this module does not create visual changes on its own, but when enabled correctly you will see classes
added and deleted from the correct page element.

No CSS is provided, so you will still need to add position and display on headroom classes.

Headroom.js will attach to the page (if enabled in settings) to the HTML element specified. On the settings page use
jQuery style selectors to indicate which element should change.

Example: "header.myheader" would initialize headroom.js on the HTML header elements of the myheader class.

For additional jQuery functions look at the code in /libraries/headroomjs/src/jQuery.headroom.js

See for more detail: http://wicky.nillia.ms/headroom.js/

Uninstallation
==============

1. Uninstall the module from the Uninstall tab or with `drush pmu headroomjs` on the command line.
2. Delete /libraries/headroomjs directory.

Support
=======

Project URL: https://www.drupal.org/project/headroomjs

For implementation issues in Drupal file issues here: https://www.drupal.org/project/issues/headroomjs
Please check your browser Javascript console to note any error messages before filing issues.

Also note that the file distributed at /src/Headroom.js does not initialize in Drupal 8 and the packaged one
from unpkg.com must be used. The file on unpkg.com includes /src/features.js , /src/Debouncer.js and
/src/Headroom.js but does not inclue angular.headroom.js or jQuery.headroom.js.

Contributors
============

* Originally developed for Drupal 7 & 8 by Kevin Quillen (kevinquillen https://www.drupal.org/u/kevinquillen )
* Updated in 2017 by Dan Feidt (HongPong https://www.drupal.org/u/hongpong )
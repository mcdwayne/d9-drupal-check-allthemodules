CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers
 
 
INTRODUCTION
------------
 
The BS Lib module provides Bootstrap 4 component library definitions and two new
blocks which you can use in your project: 'Navigation bar toggler' block which
is using Bootstrap navbar toggler component and 'Scroll to top' block which
is independent from the rest of Bootstrap (meaning you can use it in any
project).
 
  * For a full description of the module, visit the project page:
    https://drupal.org/project/bs_lib
 
  * To submit bug reports and feature suggestions, or to track changes:
    https://drupal.org/project/issues/bs_lib


REQUIREMENTS
------------

This module has no additional requirements.


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

 * Bootstrap 4 library installation:
   Download Bootstrap 4 'Source code' from https://github.com/twbs/bootstrap/releases
   and extract it. Rename extracted folder to bootstrap and place it in
   libraries folder.

 * Popper library installation:
   Download Popper library from https://github.com/FezVrasta/popper.js/releases
   (Bootstrap 4 minimal dependency is version v1.11.0) and extract it. Rename
   extracted folder to popper and place it in libraries folder.

 * Jasny Bootstrap library installation:
   Download Jasny bootstrap library from https://github.com/pivica/jasny-bootstrap/archive/master.zip
   and extract it. Rename extracted folder to jasny-bootstrap and place it in
   libraries folder.
   You need Jasny bootstrap library only if you want to use off canvas
   navigation for navigation bar. 


CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no configuration.

However, this module does define two additional blocks that you may decide to
use:

 * 'Navigation bar toggler' block which is adding Bootstrap navbar toggle button
   for navbar region expand/collapse.
 * 'Scroll to top' block which is adding a scroll to top link and offers lots
   of customizations on block configuration page.
   Do note that if you just want to use 'Scroll to top' block and you don't need
   the rest of Bootstrap libraries you do not need to download all library
   dependencies in the installation section. This block requires just jQuery and
   optionally jQuery UI which is already coming with a Drupal core.


MAINTAINERS
-----------

Current maintainers:
 * Ivica Puljic (pivica) - https://www.drupal.org/u/pivica
 
This project has been sponsored by:
 * ACTO Team - https://www.drupal.org/acto-team
   Drupal development, design and support company.
 * MD Systems - https://www.drupal.org/md-systems
   Drupal core and contributed development.  
   MD Systems is working for small start-ups as well as large Swiss corporations
   with international, multilingual websites.

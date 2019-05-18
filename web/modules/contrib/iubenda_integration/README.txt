CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------
This module provides integration between Drupal and Iubenda
https://www.iubenda.com. If you need to add the Iubenda privacy policy to your
forms, or display Iubenda EU Cookie Policy banner, this is the module you are
searching for.
This module already supports usage of Variable and Internationalization modules,
allowing you to translate you Iubenda privacy policy texts.
Iubenda Integration also provides a new block "Iubenda Integration:
Privacy policy", allowing you to say "Why another block?!"... eheh bad stories,
but sometimes could be useful :)

Features:
 * The module automatically inserts the Iubenda code in the head of every
   website pages.
 * It handles the display of privacy policy.


Iubenda Integration 8.x-2.x
---
This version is an All-in-One approach which helps you to integrate into your
Drupal website the privacy policy text, the cookie banner and the blocking
management of cookies.

Find a comprehensive guide and demo to the cookie law solution on our help blog
https://www.iubenda.com/en/help/posts/1177

This plugin works with the Iubenda Cookie Law Solution and allows to block the
most common widgets and third party cookies to comply with Cookie Laws,
particularly with the Italian cookie law implementation in mind.

Features:
 * It handles the display of cookie banners and cookie policy, saving user
   preferences about the use of cookies.
 * It displays a clean page (without banner) to users who have already provided
   their consent.
 * It detects bots/spiders and serves them a clean page.

The plugin is currently capable of automatically detecting and blocking the
following scripts:
 * Facebook widgets
 * Twitter widgets
 * Google+ widgets
 * Google AdSense
 * YouTube widgets
 * AddThis widgets
 * ShareThis widgets
 * Google Maps widgets


REQUIREMENTS
------------
Iubenda Integration 8.x-2.x work with:
 * Libraries API >= 2 (https://www.drupal.org/project/libraries)
 * Iubenda PHP Class (http://simon.s3.iubenda.com/iubenda-cookie-class)


INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.
 * Download Iubenda PHP class http://simon.s3.iubenda.com/iubenda-cookie-class.
   Unpack and rename the class directory to "iubenda_integration", afterward
   place it inside the "sites/all/libraries" directory on your server. Make sure
   the path to the class file becomes:
   "<drupal_root>/libraries/iubenda_intragration/iubenda.class.php"


CONFIGURATION
-------------
Go to "Configuration" -> "Web Services" -> "Iubenda integration" to find
all the configuration options (admin/config/services/iubenda-integration).


MAINTAINERS
-----------
Current maintainers:
 * Daniele Piaggesi (g0blin79) - https://www.drupal.org/u/g0blin79
 * Roberto Peruzzo (robertoperuzzo) - https://www.drupal.org/u/robertoperuzzo

Module development and maintenance by:
 * BMEME
   The Drupal Factory.
   Visit http://www.bmeme.com for more information.

 * STUDIO AQUA
   Drupal with web marketing around.
   Visit http://www.studioaqua.it for more information.

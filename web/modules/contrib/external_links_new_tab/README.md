CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended Modules
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This module automatically opens external (menu) links in a new tab by setting
  target="_blank" to every external link.
  
  Just enable this module and you're done.
  
  Thanks to the comment of Roman Deiloff I found on:
  https://drupal.stackexchange.com/questions/213292/open-a-link-in-a-new-tab-in-the-menu


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the external_links_new_tab module as you would normally install a
contributed Drupal
module:
- require the repository:
```
composer require drupal/external_links_new_tab --prefer-dist
```
- enable the module:
```
drush en external_links_new_tab -y
```


CONFIGURATION
--------------

There is no configuration needed for this module. Just enable and
all your external (menu) links will open in a new tab


MAINTAINERS
-----------

The 8.x.1.x branch was created by:

 * Joery Lemmens (flyke) - https://www.drupal.org/u/flyke

This module was created based on a comment from Roman Deiloff on:
https://drupal.stackexchange.com/questions/213292/open-a-link-in-a-new-tab-in-the-menu
on a topic about how to open links in a new tab.

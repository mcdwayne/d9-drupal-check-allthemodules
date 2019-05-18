CONTENTS OF THIS FILE

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This module provides a themable, CSS3 based slide-in menu mainly for mobile
site versions, with no external libraries and only basic JS used to add /
remove classes.

There are many similar modules, but what makes this one different
is that it has a Drupal 8 version, allows customization
using a template and own CSS and has no dependencies.


REQUIREMENTS
------------

No special requirements.


INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. See:
  https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
  for further information.


CONFIGURATION
-------------

* Go to blocks admin page (/admin/structure/block) and place
  the RWD Menu block in any region.
* In the block settings, chose the menu that will be used.
* For custom theming, uncheck the "Include default css" checkbox,
  copy the rwd_menu.appearance.css styles to your theme and modify
  according to requirements. Also the templates/rwd-menu.html.twig
  can be used in the site theme.


MAINTAINERS
-----------

* Marcin Grabias (Graber)  - https://www.drupal.org/u/graber

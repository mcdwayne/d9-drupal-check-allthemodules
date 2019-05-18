CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Maintainers


INTRODUCTION
------------

Inherit Link allows you to extend link action to any other parent element.
Multiple links inside inherited link element will be allowed (this will just
extend first match).

For example to link a teaser to its detail without nesting html inside inline
link html element.
And to have a teaser extending its title element linked to detail. And any other
alternative action links completly funcional.

By default (after full installation) Inherit Link will be applied to selectors:
  - .inherit-link
  - .node--view-mode-teaser
You can see all usements (config entities) into "admin/config/inherit_link".
This is made by "inherit_link_ui" module, you can add / edit / delete any
usement by interface.

Or if you prefer just a coding usement of the plugin: only enable main module,
include library when required and use.
If there is any inherit link config entity, library will be attached to page.

This module requires a jQuery plugin "InheritLink":
  https://github.com/AliagaDev/InheritLink

These are the config options:
  - Main element where inherit link will execute (and where link is located).
    Example: ".node--view-mode-teaser".
  - Link inside main element to inherit: "a" by default.
    This to make more specific link selector inside inherited element.
  - Prevent this element that may match with main selector. ".cbox" for example.
    This is to add expections that may match to link selector.
  - Hide inherited click element.
    This will hide inherited link by JS, recommended to hide using CSS.
  - Auto detect external links and open in new window.
    This will add target blank if domain is different.



REQUIREMENTS
------------

This module requires a jQuery plugin "InheritLink":
  https://github.com/AliagaDev/InheritLink


INSTALLATION
------------

Install the Inherit Link module as you would normally install a
contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
further information.

Please note:
Main module will integrate plugin with a Drupal (library).
Install also "Inherit Link UI" to have default config entities and a user
interface integration "admin/config/inherit_link".

Download and place jQuery plugin "InheritLink" into libraries folder
"libraries/InheritLink/InheritLink.js".
https://github.com/AliagaDev/InheritLink/archive/master.zip
https://github.com/AliagaDev/InheritLink


MAINTAINERS
-----------

 * CRZDEV - https://www.drupal.org/u/crzdev

Supporting organization

 * Metadrop - https://www.drupal.org/metadrop

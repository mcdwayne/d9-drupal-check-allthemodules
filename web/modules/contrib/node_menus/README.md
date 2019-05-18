Use case
========
Drupal allows to add node link into menu which is okay, 
if you have one menu and/or few links. When you build a site with 
multilingual features and create a menu per language, 
then your select box might grow really long. This module tries to help with 
this use case.

In short, this module does the following - relates the primary menu links source
variable and languages, so if you edit/add a node, 
you get the menu dropdown which contains links in that language.

Installation
============
Enable the module as usual, then you need to create the menus and 
make variable "menu_main_links_source" multilingual.

Additional notes
================
Currently there isn't any UI. You can just enable it and it should just work, 
even on existing sites. It checks if Drupal is multilingual, 
node is translatable and there are menus enabled to this node. 
For now, it does not check which menus are enabled.

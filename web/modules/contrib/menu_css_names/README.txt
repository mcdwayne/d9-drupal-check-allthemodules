Menu CSS Names
==============================================================================

This is a very simple module that takes the title of each drupal menu item and
adds it as a css class name to the menu's <li> element. Any character from 
this title that is not an alphanumeric character, dash, or underscore is
converted to a dash; all letters will be converted to lowercase. 

Using these class names, css can be used to style each menu item separately as 
needed or css sprite techniques can be used. For a menu item whose title is 
"Product Information", a typical css rule would look like this: 

  ul.menu li.product-information { font-style: bold; }

There are no admin settings for this module, it starts doing its work once
the module is enabled. All caches are automatically cleared at this time also.


Drupal 7 Version
==============================================================================
All of the core and contrib theme functions have been updated to make this
module working with Drupal 7. Also, a new function was added that provides
compatibility with the DHTML Menu module.


Compatibility
==============================================================================
Tested with the following:
- Nice Menus (7.x-2.0-beta3)
- DHTML Menu (7.x-1.0-beta1)
- Zen theme (7.x-3.1)
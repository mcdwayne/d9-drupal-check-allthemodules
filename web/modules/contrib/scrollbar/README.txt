 *Scrollbar Module*

About:
------

Scrollbar is a very simple Drupal module to implement the jScrollPane javascript
functionality to your Drupal and make the css selectors get a custom jquery 
scrollbar.

jScrollPane is a cross-browser jQuery plugin by Kelvin Luck 
(http://jscrollpane.kelvinluck.com) which converts a browser's default 
scrollbars (on elements with a relevant overflow property) into an HTML 
structure which can be easily skinned with CSS.


Installation:
-------------

- Download and install the module.
- Download all the required and complementary files from the github repo at github so they appear
under the libraries/jscrollpane folder.


Theme settings
---------------

On your theme css add one or more styles for the element you want to get the custom jQuery
scrollbar.

For example, if you want to apply the .jScrollPane() function to the .field--name-body element
just add this piece on your theme CSS

.field--name-body {
  height: 200px;
  overflow: auto;
}

For more examples of using this library please refer to the official manual at
http://jscrollpane.kelvinluck.com/index.html#examples,


Configuration
--------------

Go to admin/config/user-interface/scrollbar and configure as you want.

For more information on how to use the jScrollPane() parameters please refer to the jScrollPane settings page.

Credits
--------

Unlimited thanks to Kelvin Luck for this excellent jQuery plugin.


Maintainers
-------------

TheodorosPloumis (https://www.drupal.org/u/theodorosploumis)
Dan Feidt (https://www.drupal.org/u/hongpong)

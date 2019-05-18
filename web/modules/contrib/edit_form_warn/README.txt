Edit Form Warn


CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


INTRODUCTION
------------

The Edit Form Warn module is a simple module that adds javascript to
pages with content forms. It checks all inputs, selects, and textfields
for changes before the page is left. If any there has been a change,
then a warning message is shown before the page is left.


REQUIREMENTS
------------

This module does not have any requirements as of now.


RECOMMENDED MODULES
-------------------

There is not any modules recommended with this one.


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal 
 module. Visit:
   https://www.drupal.org/docs/user_guide/en/extend-module-install.html.


CONFIGURATION
-------------
 
There is no congiguration for this module.


TROUBLESHOOTING
---------------

 * If you are using another wysiwyg besides ckeditor, then the warning
 may not be implemented. Only ckeditor is currently supported.


FAQ
---

Q: Why does the warning not show when changes occur in my wysiwyg?

A: As of now only ckeditor is supported.

Q: Why does do I still get a warning if I undo a change in an input
field?

A: The check looks for any changes at all, even if they are undone
the warning will still fire. This is wanted to let the user know 
that they were actually working on the page. The exception for this
is the ckeditor wysiwyg, which uses the function checkDirty.


MAINTAINERS
-----------

Current maintainers:
 * Bobby Saul - https://www.drupal.org/u/bobbysaul

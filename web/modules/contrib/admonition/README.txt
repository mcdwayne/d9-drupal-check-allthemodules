ADMONITION
==========

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Known issues
 * Maintainers

INTRODUCTION
------------

The Admonition module helps authors add and edit admonitions in their content.
An admonition is advice to a reader. The following admonition types
are supported:

* Extra
* Hint
* Note
* Troubleshoot
* Warning

For a full description of the module, visit the project page:
   https://drupal.org/project/admonition

To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/admonition


REQUIREMENTS
------------

This module requires a standard installation of Drupal 8.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

Go to the list of text formats at /admin/config/content/formats. Choose a
text format that you want to add admonitions support to (e.g., Full HTML).

Drag the admonition icon from the Available buttons onto the Active toolbar.


KNOWN ISSUES
------------

There is a bug in the integration between CKEditor and the filter
"Limit allowed HTML tags and correct faulty HTML". Drupal will add something
like the following to the Allowed HTML tags list:

<div class="admonition admonition-* admonition-content">

(The class list you see depends on which plugins you are using.)

Add the following attributes to the list, after "<div" and before "class":

data-chunk-type data-extra

So it will read:

<div data-extra data-chunk-type class="admonition admonition-* admonition-content">


MAINTAINERS
-----------

Current maintainers:

 * Kieran Mathieson (mathieso) - https://drupal.org/user/1028

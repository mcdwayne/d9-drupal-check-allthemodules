#CONTENTS OF THIS FILE
---------------------
   
* Introduction
* Requirements
* Recommended modules
* Installation
* Configuration
* Troubleshooting
* FAQ
* Maintainers

#INTRODUCTION
------------

Linklay is a tool to convert images into interactive shoppable images. More than just an image mapping tool, Linklay has inbuilt analytics, auto-applied UTM links, Pinterest-friendly and much more!

The Linkay module for Drupal enables users to display a Linklay shoppable image using a customizable token. 

The syntax for the token is as follow:

[linklay:image:{identifier}]

where {identifier} looks like the following: linklay59e77f5da1dce4.21464826.

The Linklay token can use two optional modifiers separated by a semi-colon: _align_ and _class_. Those modifiers can be combined.

* [linklay:image:linklay59e77f5da1dce4.21464826;align:center]
* [linklay:image:linklay59e77f5da1dce4.21464826;class:my_css_class]
* [linklay:image:linklay59e77f5da1dce4.21464826;align:center;class:my_css_class]

The _align_ modifier allows users to force the horizontal alignment parameter to either left, center or right. Center is the default value. 

The _class_ modifier allows users to add a class attribute to the outer container. The value is arbitrary. 

*IMPORTANT*: Note that the _text format_ of the article must be set to *FULL HTML* or equivalent for the token to have an effect. 

For a full description of the module, visit the project page: https://drupal.org/project/linklay
To submit bug reports and feature suggestions, or to track changes: https://drupal.org/project/issues/linklay


REQUIREMENTS
------------

This module requires the following modules:

* Token [https://drupal.org/project/token](https://drupal.org/project/token)
* Token Filter [https://drupal.org/project/token_filter](https://drupal.org/project/token_filter)


RECOMMENDED MODULES
-------------------

* none


INSTALLATION
------------
 
* Install as you would normally install a contributed Drupal module. Visit: [https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules) for further information.


CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no configuration. When enabled, the module will prevent the links from appearing. To get the links back, disable the module and clear caches.


TROUBLESHOOTING
---------------

* There are no issues at this time.

FAQ
---

* none

MAINTAINERS
-----------

Current maintainers:
 * Linklay (linklay) - [https://www.drupal.org/u/linklay](https://www.drupal.org/u/linklay)

This project has been sponsored by:
 * LINKLAY - Linklay is a web application that enables users to create shoppable images. Visit [https://linklay.com](https://linklay.com) for more information.

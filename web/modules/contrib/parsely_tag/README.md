Parse.ly Tag
============

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Use
 * Maintainers
 * License

INTRODUCTION
------------

Parse.ly Tag gives site owners the ability to set a Token-aware Metadata tag per
content type for integration with the [Parse.ly](https://www.parse.ly/) 
analytics service.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/parsely_tag
   
 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/parsely_tag

REQUIREMENTS
------------

This module requires the following modules:

 * [Token](https://www.drupal.org/project/token)

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

CONFIGURATION
-------------

 * Configure user permissions in **Administration » People » Permissions**:

   - Administer Parse.ly Tag
   
     Full control of the Parse.ly Tag module configuration and defaults.
   
   - *Content Type*: Edit Parse.ly Tag settings
   
     Control Parse.ly Tag settings for a particular *content type*.
     
 * Configure the Parse.ly Site ID and default metadata in **Administration » 
   Configuration » Search and metadata » Parse.ly Tag**.
   
 * Configure per content type metadata via the regular content type Edit form.
   Navigate to **Administration » Structure » Content types** and select "Edit" 
   from the **Operations** select list for the desired content type.
   
Further metadata information can be found in Parse.ly's integration 
documentation: [Metadata](https://www.parse.ly/help/integration/jsonld/).

Once configuration is complete, use Parse.ly's 
[validation tool](https://www.parse.ly/help/integration/validate/) to confirm
the integration and metadata.
     
USE
---

Once configured, a metadata tag in the JSON-LD format will be automatically 
generated for all configured content.
      
MAINTAINERS
-----------

Current maintainers:
 * Christopher Charbonneau Wells (wells) - https://www.drupal.org/u/wells

This project is sponsored by:
 * [Cascade Public Media](https://www.drupal.org/cascade-public-media) for 
 [KCTS9.org](https://kcts9.org/) and [Crosscut.com](https://crosscut.com/).
 
LICENSE
-------

All code in this repository is licensed 
[GPLv2](http://www.gnu.org/licenses/gpl-2.0.html). A LICENSE file is not 
included in this repository per Drupal's module packaging specifications.

See [Licensing on Drupal.org](https://www.drupal.org/about/licensing).

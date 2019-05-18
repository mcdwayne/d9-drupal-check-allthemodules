
CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

The Cision Notify pull module provides a service endpoint that accepts HTTP
POSTSs of a pure XML document. Then provides this URL to Cision administration
to set as a service endpoint.

* For a full description of the module visit
  https://www.drupal.org/project/cision_notify_pull

* To submit bug reports and feature suggestions, or to track changes visit
  https://www.drupal.org/project/issues/cision_notify_pull


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Cision Notify pull module as you would normally install a
contributed Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

1. Navigate to Administration > Extend and enable the Cision Notify pull module.
2. Navigate to Administration > Configuration > Web Services > Cision feed
   content type.
3. Select the content type to import to the Cision feed when the HTTP post is
   made. The choice to add log messages to the Drupal log system is available.
   Save configuration.
4. From the Mapping target tab, map the target field from the selected content
   type with the appropriate Cision source from the dropdown menus. Save
   settings.
5. From the Cision testing tab enter the desired Xml code. Save configuration.

6. Check api file for how to alter node object if you need from other modules.

This module supports multilingual sites. To ensure functionality, be sure to
choose LanguageVersions Cision source to a target field.


MAINTAINERS
-----------

* Mustakimul Islam (takim) - https://www.drupal.org/u/takim

Supporting organizations:

* Digitalist Group - https://www.drupal.org/digitalist-group

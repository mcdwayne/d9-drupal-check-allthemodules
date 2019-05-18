
Copyright 2018 Hussein El-hussein


INTRODUCTION
-----------
This module allows mapping to Paragraphs fields.

FEATURES:
---------
* Supports mapping to nested Paragraphs fields.
* Supports mapping to multi-valued Paragraphs fields.
* Supports updating Paragraphs fields values.
Note: As long as the field you are trying to map to supports feeds,
then this module supports it, for example,
Interval field does not, it needs patching, see:
https://www.drupal.org/project/interval/issues/2032715

REQUIREMENTS
-------------
This module requires the following modules:

* Feeds (https://drupal.org/project/feeds)
* Paragraphs (https://drupal.org/project/paragraphs)

INSTALLATION
------------
To install, copy the feeds_para_mapper directory,
and all its contents to your modules directory.

CONFIGURATION
-------------
It has no configuration page.
To enable this module:
visit administer -> modules, and enable Paragraphs Mapper.

USAGE
-------------
For mapping multiple values, use Feeds Tamper:
https://www.drupal.org/project/feeds_tamper
And follow this guide:
https://www.drupal.org/node/2287473

Author
------
Hussein El-hussein (function.op@gmail.com)

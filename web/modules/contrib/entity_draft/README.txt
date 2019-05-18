Entity Draft Module


INTRODUCTION
=============

The Entity Draft module adds an 'is_draft' base field to bundle enabled entities and enables saving drafts of entities. It adds a "Save Draft" (text is configurable) button to entity edit forms. Unlike node's "save unpublished", entities with fields that are set to required can be saved by using the Controlled Fields module which does it's own management of the required field setting. As an entity can be in a draft state but not actually valid with respect to filled in required fields, site manager's will have to manage the draft status e.g. on an entity listing, whether to allow showing of draft entities etc.


REQUIREMENTS
============

Controlled Fields module for 'required' field management.


INSTALLATION
============

Install as you would normally install a contributed Drupal module. See: https://www.drupal.org/documentation/install/modules-themes/modules-8


MAINTAINERS
===========

Current Maintainers:
* Michael Welford - https://www.drupal.org/u/mikejw

This project has been sponsored by:
* The University of Adelaide - https://www.drupal.org/university-of-adelaide
  Visit: http://www.adelaide.edu.au

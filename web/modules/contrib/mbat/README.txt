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

This project (MBAT for short) extends the features of the Menu
Breadcrumb module to provide the same breadcrumb trail to the current
page as to a taxonomy of which it is a member, effectively giving
menu based breadcrumbs to items that aren't on a menu (e.g., blog
entries). Effectively these are "attached" to the breadcrumb trail by
taxonomy membership, inheriting the breadcrumbs of their taxonomy.


REQUIREMENTS
------------

Drupal 8 (back-port to Drupal 7 not planned: mostly due to dependency
on this design upon the applies/build architecture of D8's breadcrumb
builder interface.


RECOMMENDED MODULES
-------------------

* Taxonomy Menu (https://www.drupal.org/project/taxonomy_menu):
Note that MBAT does not create a hierarchy of breadcrumbs when the
taxonomy is hierarchical. For that use case, Taxonomy Menu is suggested
to create that hierarchy in the menu itself.


INSTALLATION
------------

Pending acceptance as a full module, download or clone via instructions on
Sandbox git page: https://www.drupal.org/project/2752393/git-instructions


CONFIGURATION
-------------

* Review high-level description on module Help page:
-- http://phair.cosd.com/admin/help/mbat

* Make sure first tick-box on MBAT settings page is selected, to enable module,
then continue with further settings below.

* Select binary options to determine appearance of breadcrumb trail.

* For each menu on site, drag to change order and select:
-- Menu box, if breadcrumbs are to be matched directly on this menu
-- Taxonomy Attachment box, if taxonomy terms on this menu are to make
their breadcrumbs available to their taxonomy members


TROUBLESHOOTING
---------------

No common issues yet.


FAQ
---

No common questions yet.


MAINTAINERS
-----------

* Robert Phair (rphair) - https://drupal.org/u/rphair

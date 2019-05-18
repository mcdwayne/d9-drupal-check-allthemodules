INTRODUCTION
------------

This module Integrates the Reference Map and Advanced Access modules generating
access records and grants based on reference maps.


REQUIREMENTS
------------

This module requires the following modules and patches:

  * Advanced Access (https://www.drupal.org/projects/adva)
    Currently the 1.x development release and following patches are required:
    * The user.advanced_access cache context doesn't exist and causes an
      AssertionError: (https://www.drupal.org/project/adva/issues/3017364)
    * Use queue api to rebuild permissions on cron:
      (https://www.drupal.org/project/adva/issues/3013798)
  * Reference Map (https://www.drupal.org/projects/reference_map)


INSTALLATION
------------

Install the module normally.


CONFIGURATION
-------------

This module provides an Advanced Access provider for Reference Maps that can be
enabled for any Advanced Access consumer. When enabled for a consumer
(/admin/config/people/adva), it finds all Advanced Access reference maps
(/admin/config/system/reference-maps) that begin with that entity type and
generates access records for all users that those maps point to. The Advanced
Access Reference Map must end with a step where the entity_type key is set to
user.


TROUBLESHOOTING
---------------

When a new Advanced Access map is created, access records will be queued to be
built during cron. To immediately build access records, click the 'Save and
Update Permissions' button or go to /admin/config/people/adva/rebuild after
saving.

This module operates under the philosophy that it's better to mistakenly deny
access than to provide it. Therefore, whenever any changes affecting access are
made, all access records are immediately invalidated. This occurs when making
access related updates to an Advanced Access reference map or to a field on an
entity type that is included in an Advanced Access reference map. Updated
access records are then created in the same manner as when creating a new
Advanced Access reference map.


MAINTAINERS
-----------

Current maintainers:

  * Charles Bamford https://www.drupal.org/u/c7bamford
  * Jon Antoine https://www.drupal.org/u/jantoine

This project was sponsored by:

  * ANTOINE SOLUTIONS  
    Specialized in consulting and architecting robust web applications powered
    by Drupal.

INTRODUCTION
------------

This module allow you build a content entity type by config .

Different from ECK module:
 * this module does not support bundle
 * this module does not support revision
 * this module allow you add base field to entity by config,
    recommend way "One entity One table"
 
CONFIGURATION
-------------
 
 * add a content entity type at admin/structure/content-types, 
   for example "author",
 * at admin/structure/content-types/manage/author, add base field to it,
   for example "Name", "Age", "Description"
 * config entity type settings, include entity keys, entity paths.
   Make sure you know what you do.
 * Save and apply update
 * manage form display at 
   admin/structure/content-types/manage/author/form-display,
   manage display at admin/structure/content-types/manage/author/display
 * add content at "/author/add", the path you could config
 * Config entity permission at admin/people/permissions

MAINTAINERS
-----------

Current maintainers:
 * howard ge (g089h515r806) - https://www.drupal.org/u/g089h515r806

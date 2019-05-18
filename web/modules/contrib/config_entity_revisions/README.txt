Config Entity Revisions
=======================

This module provides an API for augmenting Configuration entities in
Drupal 8.5 and later with revision and moderation support.

Technical explanation
---------------------

Drupal 8 has two types of entities: Configuration entities and
content entities. Configuration entities don't support versioning
or workflow. Webforms 8.x-5.x implements webforms as configuration
entities so, by default, you can't have different versions of a webform
or apply approval processes to changes in webforms. This applies across
the board to configuration entities - views, menus and other configuration
entities are also affected.

This module began life as work to implement revisioning and moderation
support for webforms by sitting a content entity alongside the config
entity, having it store a serialised version of the config entity data,
and applying revision and moderation workflows to the content entity.

Along the way, it occurred to the author that perhaps there will be other
config entities for which revisioning and workflow will be useful, so the
generic parts of the code have been split out into this module. If you'd
like to implement revisions and moderation for another configuration
entity, you can follow the pattern in the webform_revisions submodule,
which should be pretty straight forward.

TODO List
---------

(Last updated 26 July 2018)
- Display submissions using the appropriate revision.
- Implement revisions and moderation for other entity types
- Unit tests once the dust settles.

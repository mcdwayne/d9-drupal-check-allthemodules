Entity Usage
============

This module is a proof-of-concept for a tool to track usage of entities by other
entities in drupal.

Currently only the tracking of entities linked to each other using one of the
following methods is supported:
 - through entity_reference fields
 - embedded into text fields (using the "Entity Embed" module)

There is no specific configuration, once enabled, the module will start tracking
the relation between entities, getting updated on all CRUD entity operations.

A basic views integration is provided. To use the tracked information in a view,
follow the following steps:
 1) Create a view that has as base table any content entity (Node, User, Etc)
 2) Add a relationship to your view named
    "Information about the usage of this @entity_type"
 3) After adding the relationship, add the field to your view:
    "Usage count"
 4) You will probably want to enable aggregation, to avoid duplicate rows and
 have the real count sum. To do that, go to the "Advanced" section of the view
 configuration, select "Use aggregation" to "Yes"
 5) Go to the "Usage count" field you added before, open up the "Aggregation
 settings" form, and select "SUM".

In your view (or anywhere else) you can build a link to the page where you can
consult the details of the entities that use (reference) any given entity. Build
the link using the following structure:
  /admin/content/entity-usage/{entity_type}/{entity_id}
Make sure the visitors of this page have the permission to 'access entity usage
statistics' enabled.

This module is in early development phase. Please help testing it and provide
feedback on the issue queue.

==== FOR DEVELOPERS ====

The tracking information recorded by this module is stored at the "entity_usage"
table.

You can use the service
  \Drupal::service('entity_usage.usage')->listUsage($entity);
to get the statistics anywhere in your code.

If you want to provide your own tracking method (additionally to the methods
provided by the module: entity_reference and entity_embed), you only need to
implement a plugin of type EntityUsageTrack, which will be used on all CRUD
entity operations. Examples of how to implement the plugin can be found in
 src/Plugin/EntityUsage/Track

Feedback is very welcome on the issue queue.

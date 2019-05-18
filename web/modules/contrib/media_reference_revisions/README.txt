Media Reference Revisions
-------------------------
Sites which use Entity Reference fields to point to media entities have a
problem when it comes to content revisions. Should a site need to add an
editorial workflow around content with media it may have difficulties as changes
to media object are not conected to the parent node's editorial processes like
content built with Paragraphs [1] would be.

This module shoehorns revision locking onto media objects connected to other
entities using core's Entity Reference module.

A submodule is included that also extends this revision locking to media
embedded in text fields using the Entity Embed module.

Note: The module has not been tested with core 8.5's media module.


Requirements
--------------------------------------------------------------------------------
This module has one primary dependencies:

* Media Entity
  https://www.drupal.org/project/media_entity
  Provides an entity type for managing media of all sorts, including images,
  videos, remote URLs, etc.


Features
--------------------------------------------------------------------------------
The primary features include:

* Support for the Media Entity contrib module.

* Support for the Embed Entity module using the included MMR Entity Embed Filter
  submodule.

* Currently works with fields on Node entities and Paragraph entities.


Installation
--------------------------------------------------------------------------------
Because this module requires a record to be present in the
media_reference_revision table to work properly, the site will appear to not
work as intended until some content changes are made.

The follow hook_post_update_NAME() script can be used to preload a site with
somewhat appropriate values. It can be duplicated & adjusted for each entity
type that needs to be supported, the initial version just works on nodes.

Brand new sites which are starting off and do not have any content yet do not
need to have this code executed.

Make sure that the media_reference_revisions module is enabled before running
the code.

/**
 * All nodes which reference media now point to the latest media revision, so
 * that future changes to individual media objects will not be visible on the
 * live site until the nodes they're attached to / embedded on are updated.
 */
function MYMODULE_post_update_node_reference_media_revisions(&$sandbox) {
  $entity_type = 'node';
  $revision_table = 'node_revision';
  $primary_field = 'nid';
  $revision_field = 'vid';

  $entity_manager = \Drupal::entityTypeManager()->getStorage($entity_type);

  if (!isset($sandbox['current'])) {
    // Initialize the sandbox.
    $nodes = $entity_manager->loadMultiple();
    $sandbox['total'] = count($nodes);
    $sandbox['current'] = 0;
  }

  // Process the existing nodes in groups of 10.
  $increment = 10;

  // Build a query of all revisions for the entity. The variables are defined
  // above so this is safe and won't open security holes.
  $sql_query = 'SELECT ' . $revision_field;
  $sql_query .= ' FROM {' . $revision_table . '}';
  $sql_query .= ' WHERE ' . $primary_field . '=:id';
  $sql_query .= ' ORDER BY ' . $revision_field;

  // Get a list of all entities for this entity type.
  $entities = \Drupal::entityQuery($entity_type)
    ->sort($primary_field, 'ASC')
    ->accessCheck(FALSE)
    ->range($sandbox['current'], $increment)
    ->execute();

  foreach ($entities as $entity_id) {
    // Delete existing records to avoid conflicts.
    \Drupal::database()
      ->delete('media_reference_revision')
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->execute();

    // Work out the revisions to update.
    $vids = \Drupal::database()
      ->query($sql_query, [
        ':id' => $entity_id,
      ])
      ->fetchCol();

    // Loop over each existing revision.
    foreach ($vids as $vid) {
      $revision = $entity_manager->loadRevision($vid);
      media_reference_revisions_entity_insert($revision);
      mrr_embed_filter_entity_insert($revision);
    }

    $sandbox['current']++;
  }

  drupal_set_message($sandbox['current'] . ' ' . $entity_type . ' items processed.');

  // Update #finished, 1 if the the whole update has finished.
  $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
}


Known Issues
--------------------------------------------------------------------------------
* Only media objects are currently supported.
* The included text filter will be automatically installed to replace the
  normal "Display Embedded Entities" filter on any text formats which use it.
* The included text filter must not be used at the same time as the normal
  "Display Embedded Entities" filter.


Credits / contact
--------------------------------------------------------------------------------
Currently maintained by Damien McKenna [2]. Original prototype written by Nate
Andersen [3].

Ongoing development is sponsored by Mediacurrent [4].

The best way to contact the authors is to submit an issue, be it a support
request, a feature request or a bug report, in the project issue queue:
  https://www.drupal.org/project/issues/media_reference_revisions


References
--------------------------------------------------------------------------------
1: https://www.drupal.org/project/paragraphs
2: https://www.drupal.org/u/damienmckenna
3: https://www.drupal.org/u/oknate
4: https://www.mediacurrent.com/

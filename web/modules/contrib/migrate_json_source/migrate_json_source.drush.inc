<?php

/**
 * @file
 * Drush integration for migrate_json_source.
 */

function migrate_json_source_drush_command() {
  $commands['entity-json'] = [
    'description' => 'Get JSON for an entity.',
    'aliases' => ['ejs'],
    'arguments' => [
      'entity_type' => 'The entity type',
      'ids' => 'The entity IDs',
    ],
    'options' => [
      'all' => 'Get all entities of the specified type.'
    ],
  ];

  return $commands;
}

/**
 * Drush callback for entity-json command.
 */
function drush_migrate_json_source_entity_json($entity_type = NULL, $ids = NULL) {
  if (!$entity_type) {
    return drush_set_error('', dt("Must specify entity type."));
  }

  try {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
  }
  catch (\Exception $e) {
    return drush_set_error('', dt("Invalid entity type."));
  }

  $all = drush_get_option('all', FALSE);
  $ids = array_filter(explode(',', $ids));

  if ($all) {
    $output = [];
    foreach ($storage->loadMultiple() as $entity) {
      $output[$entity->id()] = $entity->toArray();
    }
    drush_print(json_encode($output, JSON_PRETTY_PRINT));
    return;
  }

  if (!$all && empty($ids)) {
    return drush_set_error('', dt("Must specify entity IDs or use --all option."));
  }

  // Single entity.
  if (count($ids) == 1) {
    $id = reset($ids);
    $entity = $storage->load($id);
    if (!$entity) {
      $args = [
        '@type' => $entity_type,
        '@id' => $id,
      ];
      return drush_set_error('', dt("Unable to load entity @type:@id", $args));
    }
    drush_print(json_encode($entity->toArray(), JSON_PRETTY_PRINT));
    return;
  }

  // Multiple entities.
  $output = [];
  foreach ($storage->loadMultiple($ids) as $entity) {
    $output[$entity->id()] = $entity->toArray();
  }
  drush_print(json_encode($output, JSON_PRETTY_PRINT));
}

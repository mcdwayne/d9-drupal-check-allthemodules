<?php

/**
 * @file
 * Drush commands for the nexx_integration module.
 */

use Drupal\Core\Entity\ContentEntityStorageBase;

/**
 * Implements hook_drush_commands().
 */
function nexx_integration_drush_command() {
  $items = [];

  $items['nexx-export-taxonomy'] = [
    'description' => "Export taxonomy terms to omnia. This inserts all terms into Omnia, it cannot check if they already exist.",
    'arguments' => [
      'source_vocabulary' => 'Drupal vocabulary to export.',
    ],
    'examples' => [
      'drush -l http://example.com nexx-export-taxonomy category' => 'Export drupal categories vocabulary to omnia',
    ],
  ];

  $items['nexx-perform-unpublish-delete'] = [
    'description' => "Delete and unpublish old videos which should be unpublished or deleted.",
  ];

  return $items;
}

/**
 * Implements the nexx-export-taxonomy drush command.
 *
 * @param string $source_vocabulary
 *   The drupal vocabulary to export.
 */
function drush_nexx_integration_nexx_export_taxonomy($source_vocabulary) {
  $type_manager = \Drupal::entityTypeManager();

  $vocabulary = $type_manager->getStorage("taxonomy_vocabulary")->load($source_vocabulary);
  if (empty($vocabulary)) {
    drush_set_error(dt("Given vocabulary !vocabulary does not exist.", ['!vocabulary' => $source_vocabulary]));
    exit;
  }

  $terms = $type_manager
    ->getStorage("taxonomy_term")
    ->loadTree($source_vocabulary, 0, NULL, TRUE);

  foreach ($terms as $term) {
    nexx_integration_taxonomy_term_update($term);
  }
}

/**
 * Implements the nexx-perform-unpublish-delete drush command.
 *
 * @param int $per_batch
 *   Limit items processed per batch.
 */
function drush_nexx_integration_nexx_perform_unpublish_delete($per_batch = 20) {
  $field_manager = \Drupal::service("entity_field.manager");

  $type = "media";
  if (!$bundle = \Drupal::config('nexx_integration.settings')->get('video_bundle')) {
    drush_set_error(dt("There is no video bundle setup. Please configure module first."));
    exit;
  }

  $field_definitions = $field_manager->getFieldDefinitions($type, $bundle);
  foreach ($field_definitions as $field_name => $field_definition) {
    if ($field_definition->getType() === 'nexx_video_data') {
      $video_field = $field_name;
      break;
    }
  }

  if (empty($video_field)) {
    drush_set_error(dt("No video data field defined."));
    exit;
  }

  $storage = \Drupal::entityTypeManager()->getStorage($type);

  /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
  $query = $storage->getQuery();

  // Unpublish videos which should be because of active or isSSC attribute.
  $group = $query->orConditionGroup()
    ->condition($video_field . '.active', 0)
    ->condition($video_field . '.isSSC', 0)
    ->condition($video_field . '.deleted', 1);

  $ids = $query
    ->condition($group)
    ->condition("status", TRUE)
    ->execute();

  $chunks = array_chunk(array_values($ids), $per_batch);
  $operations = [];

  foreach ($chunks as $chunk) {
    $operations[] = [
      "_nexx_integration_drush_batch_op",
      [
        $chunk,
        $storage,
        $video_field,
      ],
    ];
  }

  $batch = [
    'operations' => $operations,
    'title' => t('Process all old videos'),
    'init_message' => t('Initializing'),
    'error_message' => t('An error occurred'),
    'finished' => '_nexx_integration_drush_batch_finished',
  ];

  batch_set($batch);
  $batch =& batch_get();
  $batch['progressive'] = FALSE;

  // Start processing.
  drush_backend_batch_process();
}

/**
 * Perform unpublish/delete on every video in batch.
 *
 * @param array $ids
 *   List of video ids.
 * @param Drupal\Core\Entity\ContentEntityStorageBase $storage
 *   Media storage service.
 * @param string $video_field
 *   Video field name.
 * @param object $sandbox
 *   Sandbox array.
 */
function _nexx_integration_drush_batch_op(array $ids, ContentEntityStorageBase $storage, $video_field, &$sandbox) {
  /** @var \Drupal\media\MediaInterface $video */
  foreach ($storage->loadMultiple($ids) as $video) {
    /** @var \Drupal\Core\Field\FieldItemInterface $video_data */
    $video_data = $video->get($video_field)->first();
    if ($video_data->get("isDeleted")->getString() == 1) {

      drush_log(dt('Deleting video ' . $video->id()), "ok");
      $video->delete();
    }
    elseif ($video_data->get("active")->getString() == 0
      || $video_data->get("isSSC")->getString() == 0
    ) {
      $video->set("status", FALSE);
      $video->save();
      drush_log(dt('Unpublishing video ' . $video->id()), "ok");
    }
    else {
      drush_log(dt('No action video ' . $video->id()), "warning");
    }
  }
  drush_log(dt('Batch finished...'), "ok");
}

/**
 * Perform this function after all batches are done.
 *
 * @param bool $success
 *   Indicate that the batch API tasks were all completed successfully.
 * @param array $results
 *   An array of all the results that were updated in update_do_one().
 * @param array $operations
 *   A list of all the operations that had not been completed by the batch
 *   API.
 */
function _nexx_integration_drush_batch_finished($success, array $results, array $operations) {
  drush_log(dt('Operation finished'), "ok");
}

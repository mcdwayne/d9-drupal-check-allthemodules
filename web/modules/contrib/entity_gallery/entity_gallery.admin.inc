<?php

/**
 * @file
 * Galleries administration and module settings user interface.
 */

use Drupal\entity_gallery\EntityGalleryInterface;
use Drupal\entity_gallery\Entity\EntityGallery;

/**
 * Updates all entity galleries in the passed-in array with the passed-in field
 * values.
 *
 * IMPORTANT NOTE: This function is intended to work when called from a form
 * submission handler. Calling it outside of the form submission process may not
 * work correctly.
 *
 * @param array $entity_galleries
 *   Array of entity gallery egids or entity galleries to update.
 * @param array $updates
 *   Array of key/value pairs with entity gallery field names and the value to
 *   update that
 *   field to.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 * @param bool $load
 *   (optional) TRUE if $entity_galleries contains an array of entity gallery
 *   IDs to be loaded, FALSE if it contains fully loaded entity galleries.
 *   Defaults to FALSE.
 * @param bool $revisions
 *   (optional) TRUE if $entity_galleries contains an array of revision IDs
 *   instead of entity gallery IDs. Defaults to FALSE; will be ignored if $load
 *   is FALSE.
 */
function entity_gallery_mass_update(array $entity_galleries, array $updates, $langcode = NULL, $load = FALSE, $revisions = FALSE) {
  // We use batch processing to prevent timeout when updating a large number
  // of entity galleries.
  if (count($entity_galleries) > 10) {
    $batch = array(
      'operations' => array(
        array('_entity_gallery_mass_update_batch_process', array($entity_galleries, $updates, $langcode, $load, $revisions))
      ),
      'finished' => '_entity_gallery_mass_update_batch_finished',
      'title' => t('Processing'),
      // We use a single multi-pass operation, so the default
      // 'Remaining x of y operations' message will be confusing here.
      'progress_message' => '',
      'error_message' => t('The update has encountered an error.'),
      // The operations do not live in the .module file, so we need to
      // tell the batch engine which file to load before calling them.
      'file' => drupal_get_path('module', 'entity_gallery') . '/entity_gallery.admin.inc',
    );
    batch_set($batch);
  }
  else {
    if ($load && !$revisions) {
      $entity_galleries = EntityGallery::loadMultiple($entity_galleries);
    }
    foreach ($entity_galleries as $entity_gallery) {
      if ($load && $revisions) {
        $entity_gallery = entity_revision_load('entity_gallery', $entity_gallery);
      }
      _entity_gallery_mass_update_helper($entity_gallery, $updates, $langcode);
    }
    drupal_set_message(t('The update has been performed.'));
  }
}

/**
 * Updates individual entity galleries when fewer than 10 are queued.
 *
 * @param \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery
 *   An entity gallery to update.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 *
 * @return \Drupal\entity_gallery\EntityGalleryInterface
 *   An updated entity gallery object.
 *
 * @see entity_gallery_mass_update()
 */
function _entity_gallery_mass_update_helper(EntityGalleryInterface $entity_gallery, array $updates, $langcode = NULL) {
  $langcodes = isset($langcode) ? array($langcode) : array_keys($entity_gallery->getTranslationLanguages());
  // For efficiency manually save the original entity gallery before applying any changes.
  $entity_gallery->original = clone $entity_gallery;
  foreach ($langcodes as $langcode) {
    foreach ($updates as $name => $value) {
      $entity_gallery->getTranslation($langcode)->$name = $value;
    }
  }
  $entity_gallery->save();
  return $entity_gallery;
}

/**
 * Implements callback_batch_operation().
 *
 * Executes a batch operation for entity_gallery_mass_update().
 *
 * @param array $entity_galleries
 *   An array of entity gallery IDs.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   The language updates should be applied to. If none is specified all
 *   available languages are processed.
 * @param bool $load
 *   TRUE if $entity_galleries contains an array of entity gallery IDs to be
 *   loaded, FALSE if it contains fully loaded entity galleries.
 * @param bool $revisions
 *   (optional) TRUE if $entity_galleries contains an array of revision IDs
 *   instead of entity gallery IDs. Defaults to FALSE; will be ignored if $load
 *   is FALSE.
 * @param array|\ArrayAccess $context.
 *   An array of contextual key/values.
 */
function _entity_gallery_mass_update_batch_process(array $entity_galleries, array $updates, $langcode, $load, $revisions, &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($entity_galleries);
    $context['sandbox']['entity_galleries'] = $entity_galleries;
  }

  // Process entity galleries by groups of 5.
  $count = min(5, count($context['sandbox']['entity_galleries']));
  for ($i = 1; $i <= $count; $i++) {
    // For each egid, load the entity gallery, reset the values, and save it.
    $entity_gallery = array_shift($context['sandbox']['entity_galleries']);
    if ($load) {
      $entity_gallery = $revisions ?
        entity_revision_load('entity_gallery', $entity_gallery) : EntityGallery::load($entity_gallery);
    }
    $entity_gallery = _entity_gallery_mass_update_helper($entity_gallery, $updates, $langcode);

    // Store result for post-processing in the finished callback.
    $context['results'][] = \Drupal::l($entity_gallery->label(), $entity_gallery->urlInfo());

    // Update our progress information.
    $context['sandbox']['progress']++;
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Implements callback_batch_finished().
 *
 * Reports the 'finished' status of batch operation for
 * entity_gallery_mass_update().
 *
 * @param bool $success
 *   A boolean indicating whether the batch mass update operation successfully
 *   concluded.
 * @param string[] $results
 *   An array of rendered links to entity galleries updated via the batch mode
 *   process.
 * @param array $operations
 *   An array of function calls (not used in this function).
 *
 * @see _entity_gallery_mass_update_batch_process()
 */
function _entity_gallery_mass_update_batch_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('The update has been performed.'));
  }
  else {
    drupal_set_message(t('An error occurred and processing did not complete.'), 'error');
    $message = \Drupal::translation()->formatPlural(count($results), '1 item successfully processed:', '@count items successfully processed:');
    $item_list = array(
      '#theme' => 'item_list',
      '#items' => $results,
    );
    $message .= drupal_render($item_list);
    drupal_set_message($message);
  }
}

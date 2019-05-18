<?php
/**
 * @file
 * Batch function.
 *
 * This perfoms all the fun batch ops for the module.
 */

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\file\Entity\File;
use Drupal\Core\Database\Database;

/**
 * Batch function.
 */
function fancy_file_delete_batch($fid, $force, &$context) {
  // Update our progress information.
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
  }
  $context['sandbox']['progress']++;

  // Manual / Orphan Delete.
  if (is_numeric($fid)) {
    $file = File::load($fid);

    if ($file) {
      if ($force) {
        // Remove these from the DB
        $connection = Database::getConnection();
        $connection->delete('file_managed')
          ->condition('fid', $fid)
          ->execute();
        $connection->delete('file_usage')
          ->condition('fid', $fid)
          ->execute();
        // Now Delete the file completely.
        // Skip file api and just delete the entity, quicker.
        $controller = \Drupal::entityManager()->getStorage('file');
        $entity = $controller->loadMultiple([$fid]);
        $controller->delete($entity);
      }
      else {
        $file->delete();
      }
      $context['results'][] = $fid;
    }
  }
  // Delete unmanaged.
  else {
    // @todo fix this to be the new way.
    db_delete('unmanaged_files')
    ->condition('path', $fid)
    ->execute();
    file_unmanaged_delete($fid);
    $context['results'][] = $fid;
  }
  // @todo Probably need to add some logic in case there were failures, etc.
  $context['message'] = t('Now cleansing the system of fid#%fid', array('%fid' => $fid));
}

/**
 * Batch finished.
 */
function fancy_file_delete_batch_finished($success, $results, $operations) {
  if ($success) {
    // Reset the cache
    \Drupal::entityManager()->getStorage('file')->resetCache();
    $message = \Drupal::translation()->formatPlural(count($results), 'One file cleansed.', '@count files cleansed.');
  }
  else {
    $message = t('Assimilation was futile!');
  }

  drupal_set_message($message);

  return new RedirectResponse(\Drupal::url('fancy_file_delete.info'));
}

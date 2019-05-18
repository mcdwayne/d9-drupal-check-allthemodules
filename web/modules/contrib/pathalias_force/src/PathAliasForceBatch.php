<?php

/**
 * @file
 * Helper class which provides methods for the batching process.
 */

namespace Drupal\pathalias_force;

class PathAliasForceBatch {

  /**
   * Callback batch operation.
   *
   * @param string $source
   *  Path source.
   * @param string $langcode
   *  Path langcode.
   * @param array $context
   *  Gathers batch context information.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function recreate($source, $langcode, &$context) {
    $message = 'Recreating';

    $axis_path_alias_force_storage = \Drupal::service('path.alias_storage');
    if ($path = $axis_path_alias_force_storage->load(['source' => $source, 'langcode' => $langcode, 'forced' => 0])) {
      $context['results'][] = pathalias_force_updated($path);
    }

    $context['message'] = $message;
  }

  /**
   * Callback batch finished.
   *
   * @param $success
   *  A boolean indicating whether the batch has completed successfully.
   * @param $results
   *  The value set in $context['results'] by callback_batch_operation().
   * @param $operations
   *  If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function finished($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One URL alias processed.', '@count URL aliases processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
  }

}

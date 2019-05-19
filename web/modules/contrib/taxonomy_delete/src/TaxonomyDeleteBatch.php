<?php

namespace Drupal\taxonomy_delete;

/**
 * Batch Processor of the deletion of terms.
 */
class TaxonomyDeleteBatch {

  /**
   * Process the vocabulary and delete all the terms associated with it.
   *
   * @param string $vid
   *   The vocabulary ID.
   * @param array $context
   *   The batch context.
   */
  public static function processVocabulary($vid, array $context) {
    $service = \Drupal::service('taxonomy_delete.term_delete');
    $term_count = $service->deleteTermByVid($vid);
    $context['results']['terms'] = $term_count;
    $context['results']['vid'] = $vid;
  }

  /**
   * Display message about how many terms are deleted in the vocabulary.
   *
   * @param bool $success
   *   Success flag.
   * @param array $results
   *   Results from the batch handler.
   * @param array $operations
   *   Operations flag.
   */
  public static function finishProcess($success, array $results, array $operations) {
    if ($success) {
      $message = \Drupal::translation()
        ->formatPlural($results['terms'], 'One term deleted in ', '@count terms deleted in @vid', [
          '@vid' => $results['vid'],
        ]);
    }
    else {
      $message = t("Processed with errors");
    }
    drupal_set_message($message);
  }

}

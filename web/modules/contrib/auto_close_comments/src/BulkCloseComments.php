<?php

namespace Drupal\auto_close_comments;

/**
 * Provides methods to handle comments closing.
 */
class BulkCloseComments {

  /**
   * Callback function to close comment for particular nodes.
   */
  public static function closeComments($nids, &$context) {
    $message = 'Closing Comments...';
    $results = [];
    foreach ($nids as $nid) {
      $upd = \Drupal::database()->update('node__comment');
      $upd->fields(['comment_status' => 1]);
      $upd->condition('entity_id', $nid, '=');
      $upd->execute();
      \Drupal::entityManager()
        ->getStorage('node')
        ->resetCache([
          $nid,
        ]);
    }
    $context['message'] = $message;
    $context['results'] = $nids;
  }

  /**
   * Finished callback function.
   */
  public function closeCommentsFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One node has been processed.', '@count node has been processed.'
      );
    }
    else {
      $message = t('Encountered some error.');
    }
    drupal_set_message($message);
  }

}

<?php

/**
 * @file
 * Contains callbacks for batch process.
 */

namespace Drupal\termcase;

/**
 * Class, which contains the callbacks of a batch process.
 */
class UpdateTerms {

  public static function updateAllTerms($terms, $case, &$context){
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_term'] = 0;
      $context['sandbox']['max'] = count($terms);
    }
    // Process 5 terms at a time.
    $limit = 5;
    $terms = array_slice($terms, $context['sandbox']['progress'], $limit);
    $message = 'Updating Terms...';
    $results = array();
    foreach ($terms as $term) {
      $converted_term = _termcase_convert_string_to_case($term->name, $case);
      termcase_update_term($converted_term, $term->tid, $term->vid);

      // Store some result for post-processing in the finished callback.
      $context['results'][] = $term->name;

      // Update our progress information.
      $context['sandbox']['progress']++;
      $context['sandbox']['current_term'] = $term->tid;
      $context['message'] = t('Now processing %term', array('%term' => $term->name));
    }

    // Inform the batch engine that we are not finished,
    // and provide an estimation of the completion level we reached.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }

  }

  public static function updateAllTermsFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One post processed.', '@count posts processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }
}

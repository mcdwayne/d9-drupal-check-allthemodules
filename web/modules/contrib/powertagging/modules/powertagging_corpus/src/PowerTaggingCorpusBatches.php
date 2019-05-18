<?php

/**
 * @file
 * A collection of methods required for batches of the PowerTagging Corpus module.
 */

namespace Drupal\powertagging_corpus;

/**
 * A collection of static functions offered by the PoolParty Taxonomy Manager module.
 */
class PowerTaggingCorpusBatches {
  /**
   * Push a set of entities into the corpus of a PoolParty project in a batch.
   *
   * @param array $entities_info
   *   Array of entity information, each item is an ssociative array itself
   *   containing following properties:
   *   - "id" --> The ID of the entity.
   *   - "entity_type" --> The entity type.
   *   - "bundle" --> The bundle of the entity.
   *   - "content_type" --> basically the bundle, but using the vocabulary ID for
   *     taxonomy terms instead of the machine name.
   * @param array $content_selected
   *   Associative array of content and fields to push into the corpus:
   *   entity type --> content type --> field id --> field id
   * @param array $corpus_details
   *   Associative array of information about the corpus containing following
   *   properties:
   *   - "connection_id" --> The ID of the PowerTagging connection to use.
   *   - "project_id" --> The ID of the project to use.
   *   - "corpus_id" --> The ID of the corpus to use.
   * @param array $batch_info
   *   An associative array of information about the batch process.
   * @param array $context
   *   The Batch context to transmit data between different calls.
   */
  public static function pushEntity(array $entities_info, array $content_selected, array $corpus_details, array $batch_info, &$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
    }

    foreach ($entities_info as $entity_info) {
      $fields_to_use = array_keys($content_selected[$entity_info['entity_type']][$entity_info['content_type']]);
      PowerTaggingCorpus::pushEntityToCorpus($entity_info, $fields_to_use, $corpus_details);
      $context['results']['processed']++;
    }

    $context['results']['end_time'] = time();
  }

  /**
   * Batch process finished function for pushing entities into a corpus.
   */
  public static function pushEntityFinished($success, $results, $operations) {
    if ($success) {
      $message = t('Successfully finished pushing %total_entities entities into the selected corpus on %date:', [
        '%total_entities' => $results['processed'],
        '%date' => \Drupal::service('date.formatter')
          ->format($results['end_time'], 'short')
      ]);
      drupal_set_message($message);
    }
    else {
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation on %date', array(
          '%error_operation' => $error_operation[0],
          '%date' => \Drupal::service('date.formatter')
            ->format($results['end_time'], 'short'),
        )) . '<br />';
      $message .= t('<ul><li>arguments: %arguments</li></ul>', array(
        '@arguments' => print_r($error_operation[1], TRUE),
      ));
      drupal_set_message($message, 'error');
    }
  }
}

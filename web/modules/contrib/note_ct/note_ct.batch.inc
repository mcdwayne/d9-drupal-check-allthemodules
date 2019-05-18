<?php
/**
 * @file contains batch function
 */

use \Drupal\node\Entity\Node;

/**
 * Class NoteBatch
 */
class NoteBatch {

  function SetNoteToNone($nid, &$context) {
    if (empty($context['results'])) {
      $context['results']['progress_result'];
    }

    $node = Node::load($nid);
    $node->set('note_status', 'actual');
    $node->save();
    $context['results']['progress_result']++;
  }

  function UpdateStatus($nid, $date, &$context) {
    if (empty($context['results'])) {
      $context['results']['progress_result'];
    }

    if ($date >= date('Y.m.d')) {
      $node = Node::load($nid);
      $node->set('note_status', 'actual');
      $node->save();
    }
    else {
      $node = Node::load($nid);
      $node->set('note_status', 'expired');
      $node->save();
    }
    $context['results']['progress_result']++;
  }

  function BatchFinished($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addMessage(t('@count Notes processed.', ['@count' => $results['progress_result']]));
    }
    else {
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }
}

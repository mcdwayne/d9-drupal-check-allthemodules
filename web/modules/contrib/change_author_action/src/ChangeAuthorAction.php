<?php

namespace Drupal\change_author_action;

/**
 * ChangeAuthorAction.
 */
class ChangeAuthorAction {

  /**
   * {@inheritdoc}
   */
  public static function updateFields($entities, $new_author, &$context) {
    $message = 'Changing author...';
    $results = [];
    foreach ($entities as $entity) {
      if($entity->getOwnerId() != $new_author){
        $entity->setOwnerId($new_author);
        $entity->setNewRevision();
        $entity->save();
      }else{
      }
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  /**
   * {@inheritdoc}
   */
  public static function changeAuthorActionFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
//      $message = \Drupal::translation()->formatPlural(
//        count($results['changed_author']),
//        'One operations processed.', '@count authors have been changed.'
//      );
    }
    else {
      $message = t('Finished with an error.');
      drupal_set_message($message);
    }
  }

}

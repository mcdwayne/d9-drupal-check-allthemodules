<?php

namespace Drupal\friends\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;

/**
 * Provides a 'FriendsActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "friends_activity_context",
 *  label = @Translation("Friends activity context"),
 * )
 */
class FriendsActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_object = $data['related_object'][0];
      if ($related_object['target_type'] == 'friends') {
        $friends_storage = \Drupal::entityTypeManager()->getStorage('friends');

        $friends = $friends_storage->load($related_object['target_id']);

        // Owner just create request send notification to Recipient.
        if ($friends->getUpdaterId() == $friends->getOwnerId()) {
          $recipients[] = [
            'target_type' => 'user',
            'target_id' => $friends->getRecipientId(),
          ];
        }
        // Recipient updated request send.
        elseif ($friends->getUpdaterId() == $friends->getRecipientId()) {
          $recipients[] = [
            'target_type' => 'user',
            'target_id' => $friends->getOwnerId(),
          ];
        }
      }
    }
    return $recipients;
  }

  /**
   * Check if it's valid.
   */
  public function isValidEntity($entity) {
    if ($entity->getEntityTypeId() === 'friends') {
      return TRUE;
    }

    return FALSE;
  }

}

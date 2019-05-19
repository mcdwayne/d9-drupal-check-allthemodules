<?php

namespace Drupal\user_bundle;

use Drupal\user\UserStorage;

/**
 * Controller class for typed users.
 *
 * This extends the Drupal\user\UserStorage class, adding required special
 * handling for bundle-aware user objects.
 */
class TypedUserStorage extends UserStorage implements TypedUserStorageInterface {

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    // The default user type is "user".
    if (!isset($values['type'])) {
      $values['type'] = 'user';
    }
    return parent::doCreate($values);
  }

  /**
   * {@inheritdoc}
   */
  public function updateType($old_type, $new_type) {
    return $this->database->update('users')
      ->fields(['type' => $new_type])
      ->condition('type', $old_type)
      ->execute();
  }

}

<?php

/**
 * @file
 * Contains \Drupal\itemsessionlock_example\Plugin\ItemSessionLock\UserEdit.
 */

namespace Drupal\itemsessionlock_example\Plugin\ItemSessionLock;

use Drupal\itemsessionlock\Plugin\ItemSessionLock\ItemSessionLockBase;

/**
 * Provides a 'User edit' lock.
 *
 * @ItemSessionLock(
 *   id = "itemsessionlock_example_user_edit",
 *   label = @Translation("User")
 * )
 */
class UserEdit extends ItemSessionLockBase {

}
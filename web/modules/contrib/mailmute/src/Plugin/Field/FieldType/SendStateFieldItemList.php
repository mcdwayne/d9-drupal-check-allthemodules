<?php
/**
 * @file
 * Contains \Drupal\mailmute\Plugin\Field\FieldType\SendStateFieldItemList.
 */

namespace Drupal\mailmute\Plugin\Field\FieldType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;

/**
 * Represents a send state field; that is, a list of (one) send state item.
 *
 * @ingroup field
 */
class SendStateFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    // Allow if user has admin permission.
    return AccessResult::allowedIfHasPermission($account, 'administer mailmute')
      // Allow if user has 'change own' permission and is operating on themself.
      ->orIf(AccessResult::allowedIfHasPermission($account, 'change own send state')
          ->andIf(AccessResult::allowedIf(isset($account) && $this->getEntity()->id() == $account->id())))
      // Restrict on parent.
      ->andIf(parent::defaultAccess($operation, $account));
  }

}

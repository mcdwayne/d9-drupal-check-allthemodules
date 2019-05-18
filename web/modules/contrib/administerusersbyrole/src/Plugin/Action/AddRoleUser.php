<?php

namespace Drupal\administerusersbyrole\Plugin\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\Plugin\Action\AddRoleUser as AddRoleUserBase;

/**
 * Alternative implementation for Action id = "user_add_role_action".
 */
class AddRoleUser extends AddRoleUserBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = parent::access($object, $account, TRUE)
      ->orIf(administerusersbyrole_user_assign_role($object, $account, [$this->configuration['rid']]));

    return $return_as_object ? $access : $access->isAllowed();
  }

}

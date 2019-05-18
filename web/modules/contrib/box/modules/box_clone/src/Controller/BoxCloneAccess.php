<?php

namespace Drupal\box_clone\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\box\Entity\Box;

class BoxCloneAccess {

  /**
   * Limit access to the clone according to their restricted state.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\box\Entity\Box $box
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function cloneBox(AccountInterface $account, Box $box) {
    $bundle = $box->bundle();
    $result = AccessResult::allowedIfHasPermissions($account, [
      "clone {$bundle} box",
      "create {$bundle} box",
    ]);

    $result->addCacheableDependency($box);

    return $result;
  }

}

<?php

namespace Drupal\simply_signups\Access;

use Drupal\node\Entity\Node;
use Drupal\Core\Access\AccessResult;

/**
 * Check the access to a node task based on the node type.
 */
class SimplySignupsAccess {

  /**
   * Implements a custom access check.
   */
  public function accessSettings($node) {
    $account = \Drupal::currentUser();
    $nodeObject = Node::load($node);
    $node = $nodeObject;
    $config = \Drupal::config('simply_signups.config');
    $bundles = $config->get('bundles');
    $bundle = $node->bundle();
    $bundleAccess = (in_array($bundle, $bundles, TRUE)) ? 1 : 0;
    $permission = ($account->hasPermission('edit_simply_signups') == 1) ? 1 : 0;
    $y = 0;
    if (($permission == 1) and ($bundleAccess == 1)) {
      $y = 1;
    }
    return ($y == 1) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Implements accessSignup permission check.
   */
  public function accessSignup($node) {
    $account = \Drupal::currentUser();
    $nodeObject = Node::load($node);
    $node = $nodeObject;
    $config = \Drupal::config('simply_signups.config');
    $bundles = $config->get('bundles');
    $bundle = $node->bundle();
    $bundleAccess = (in_array($bundle, $bundles, TRUE)) ? 1 : 0;
    $permission = ($account->hasPermission('view_simply_signups') == 1) ? 1 : 0;
    $y = 0;
    if (($permission == 1) and ($bundleAccess == 1)) {
      $y = 1;
    }
    return ($y == 1) ? AccessResult::allowed() : AccessResult::forbidden();
  }

}

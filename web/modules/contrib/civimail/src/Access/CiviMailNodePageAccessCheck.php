<?php

namespace Drupal\civimail\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Access to the CiviMail feature based on the content type configuration.
 */
class CiviMailNodePageAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match) {
    $nodeId = $route_match->getParameter('node');
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nodeId);
    return AccessResult::allowedIf(civimail_get_entity_bundle_settings('enabled', 'node', $node->getType()));
  }

}

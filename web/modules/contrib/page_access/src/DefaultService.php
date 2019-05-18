<?php

namespace Drupal\page_access;

use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class PageAccess.
 *
 * @package Drupal\page_access
 */
class DefaultService {

  /**
   * Service method to get user access for the node page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User object.
   *
   * @return array|null
   */
  function get_user_node_access(NodeInterface $node, AccountInterface $account) {
    $page_access_settings = page_access_get_node_defaults($node);
    if (!empty($page_access_settings['id'])) {
      $user_has_access = [];
      $user_roles = $account->getRoles();
      $allowed_roles = $page_access_settings['page_access']['roles'];
      if (!empty(array_intersect($user_roles, $allowed_roles))) {
        return ['view', 'update'];
      }

      $uid = $account->id();
      foreach ($page_access_settings['page_access']['users'] as $access) {
        if ($access['user_autocomplete'] === $uid) {
          $user_has_access[] = 'view';
          if ($access['edit_permission']) {
            $user_has_access[] = 'update';
          }
          break;
        }
      }
      return $user_has_access;
    }
    return NULL;
  }

}

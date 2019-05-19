<?php

namespace Drupal\user_request\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user_request\Entity\RequestInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks access to edit the request.
 */
class RequestEditFormAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Gets the request from the route and checks only the permissions 
    // associated with request states.
    if ($request = $route_match->getParameter('user_request')) {
      return $this->checkRequestStatePermissions($request, 'update', $account);
    }
    return AccessResult::neutral();
  }

  /**
   * Checks permission to perform operations when the request's state has some value.
   *
   * @param \Drupal\Core\Entity\RequestInterface $entity
   *   The request entity for which to check access.
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view", "view label", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  protected function checkRequestStatePermissions(RequestInterface $entity, $operation, AccountInterface $account) {
    // Checks generic permissions.
    $state = $entity->getStateString();
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $permissions = [
      "$operation any $state $entity_type_id",
      "$operation any $state $bundle $entity_type_id",
    ];
    $result = AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');

    if ($result->isNeutral()) {
      // Checks owner's permissions.
      if ($entity->getOwnerId() == $account->id()) {
        $permissions = [
          "$operation own $state $entity_type_id",
          "$operation own $state $bundle $entity_type_id",
        ];
        $result = AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
      }
    }

    return $result;
  }

}

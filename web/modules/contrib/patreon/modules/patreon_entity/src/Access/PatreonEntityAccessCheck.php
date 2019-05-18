<?php

namespace Drupal\patreon_entity\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\patreon_entity\Entity\PatreonEntity;

/**
 * Class PatreonEntityAccessCheck.
 *
 * @package Drupal\patreon_entity
 */
class PatreonEntityAccessCheck implements AccessInterface {

  /**
   * Revision access for the Patreon Entity.
   *
   * @param PatreonEntity $patreon_entity
   *   The current entity being viewed.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Whether the user has access or not.
   */
  public function access(PatreonEntity $patreon_entity) {
    $account = \Drupal::currentUser();
    $permissions = [
      'access patreon entity revisions',
      'view ' . $patreon_entity->gettype() . ' revisions',
    ];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}

<?php

namespace Drupal\patreon_entity\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\patreon_entity\Entity\PatreonEntity;
use Drupal\Core\Access\AccessResult;

/**
 * Class PatreonEntityRevertAccessCheck.
 *
 * @package Drupal\patreon_entity
 */
class PatreonEntityRevertAccessCheck implements AccessInterface {

  /**
   * Revision revert access for the Patreon Entity.
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
      'revert all patreon entity revisions',
      'revert ' . $patreon_entity->getType() . ' revisions',
    ];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}

<?php

namespace Drupal\keep_referenced_entities\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access to the list of referenced entities.
 */
class ReferencedEntitiesList implements AccessInterface {

  /**
   * Access callback to check an access to the list of referenced entities.
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function viewList(AccountInterface $account, $entity_type, $entity_id) {
    if ($entity_manager = \Drupal::getContainer()->get('entity.manager')->getStorage($entity_type)) {
      if ($entity = $entity_manager->load($entity_id)) {
        // If user has an ability to remove the entity.
        if ($entity->access('delete', $account)) {
          return AccessResult::allowed();
        }
      }
    }
    // Forbidden by default.
    return AccessResult::forbidden();
  }
}

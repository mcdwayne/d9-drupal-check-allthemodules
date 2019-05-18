<?php

namespace Drupal\entity_pilot\Plugin\entity_pilot\Exists;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\entity_pilot\ExistsPluginInterface;

/**
 * Defines a plugin for finding existing entities using UUID.
 *
 * @EntityPilotExists(
 *   id = "user_exists_by_name"
 * )
 */
class UserExistsByName extends PluginBase implements ExistsPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function exists(EntityManagerInterface $entity_manager, EntityInterface $passenger) {
    if ($passenger->getEntityTypeId() == 'user') {
      // We use the raw get here to avoid having any module's implementations of
      // hook_user_format_name invoked.
      if ($users = $entity_manager->getStorage('user')->loadByProperties(['name' => $passenger->get('name')->value])) {
        return reset($users);
      }
    }
    return FALSE;
  }

  /**
   * Allows plugin to apply modify incoming entities from existing one.
   *
   * @param \Drupal\Core\Entity\EntityInterface $incoming
   *   The entity imported from EntityPilot.
   * @param \Drupal\Core\Entity\EntityInterface $existing
   *   The existing entity that matches.
   */
  public function preApprove(EntityInterface $incoming, EntityInterface $existing) {
    if ($incoming->getEntityTypeId() == 'user') {
      // We match up the incoming UID.
      $id_field = $incoming->getEntityType()->getKey('id');
      $incoming->set($id_field, $existing->id());
      $incoming->enforceIsNew(FALSE);
    }
  }

}

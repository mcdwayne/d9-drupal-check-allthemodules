<?php

namespace Drupal\entity_pilot\Plugin\entity_pilot\Exists;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\entity_pilot\ExistsPluginInterface;

/**
 * Defines a plugin for finding existing entities using UUID.
 *
 * @EntityPilotExists(
 *   id = "exists_by_uuid"
 * )
 */
class ExistsByUuid extends PluginBase implements ExistsPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function exists(EntityManagerInterface $entity_manager, EntityInterface $passenger) {
    return $entity_manager->loadEntityByUuid($passenger->getEntityTypeId(), $passenger->uuid());
  }

  /**
   * {@inheritdoc}
   */
  public function preApprove(EntityInterface $incoming, EntityInterface $existing) {
    if ($incoming->uuid() == $existing->uuid()) {
      // We match up the incoming ID based on UUID.
      $id_field = $incoming->getEntityType()->getKey('id');
      $incoming->set($id_field, $existing->id());
      $incoming->enforceIsNew(FALSE);
      if ($incoming instanceof RevisionableInterface && $incoming->getEntityType()->hasKey('revision')) {
        $incoming->setNewRevision(TRUE);
      }
    }
  }

}

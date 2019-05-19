<?php

namespace Drupal\workflow_participants;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage handler for workflow participant entities.
 */
class WorkflowParticipantsStorage extends SqlContentEntityStorage implements WorkflowParticipantsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadForModeratedEntity(ContentEntityInterface $entity) {
    if (!$entity->id()) {
      throw new \LogicException('Cannot load workflow participants for new entities.');
    }
    $existing = $this->loadByProperties([
      'moderated_entity__target_type' => $entity->getEntityTypeId(),
      'moderated_entity__target_id' => $entity->id(),
    ]);
    if (!empty($existing)) {
      return reset($existing);
    }
    return $this->create([
      'moderated_entity' => $entity,
    ]);
  }

}

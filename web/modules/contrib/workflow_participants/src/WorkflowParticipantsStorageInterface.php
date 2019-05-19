<?php

namespace Drupal\workflow_participants;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defineds workflow participants storage interface.
 */
interface WorkflowParticipantsStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads or creates a workflow participant entity for a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being moderated.
   *
   * @return \Drupal\workflow_participants\Entity\WorkflowParticipantsInterface
   *   The workflow participants entity for the given moderated entity.
   */
  public function loadForModeratedEntity(ContentEntityInterface $entity);

}

<?php

namespace Drupal\workflow_participants;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\workflow_participants\Entity\Routing\RouteProvider;

/**
 * Manipulates entity type information on behalf of workflow participants.
 */
class EntityTypeInfo {

  /**
   * Adds workflow participant functionality for relevant entities.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function alter(array &$entity_types) {
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type->isRevisionable() && $entity_type_id !== 'workflow_participants') {
        $this->addWorkflowParticipantsEntityType($entity_type);
      }
    }
  }

  /**
   * Adds workflow participant functionality to a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $type
   *   The entity type for which to add workflow participant functionality.
   */
  protected function addWorkflowParticipantsEntityType(EntityTypeInterface $type) {
    // Set workflow participants link template.
    if (!$type->hasLinkTemplate('workflow-participants')) {
      $type->setLinkTemplate('workflow-participants', $type->getLinkTemplate('canonical') . '/workflow-participants');
    }

    // Add workflow participants route provider.
    $providers = $type->getRouteProviderClasses();
    if (empty($providers['workflow_participants'])) {
      $providers['workflow_participants'] = RouteProvider::class;
      $type->setHandlerClass('route_provider', $providers);
    }
  }

}

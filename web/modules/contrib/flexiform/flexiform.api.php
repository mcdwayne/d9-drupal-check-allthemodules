<?php

/**
 * @file
 * Hooks specific to flexiform.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\flexiform\FormEntity\FlexiformFormEntityInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * React to an entity being created by a form entity.
 *
 * Sometimes FormEntity plugins create a new entity for the relationship they
 * represent (e.g. entity reference fields). This hook fires immediately after
 * the new entity is created and before the form is build.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity that has just been created.
 * @param \Drupal\flexiform\FormEntity\FlexiformFormEntityInterface $plugin
 *   The plugin that has just created the entity.
 */
function flexiform_flexiform_form_entity_entity_create(EntityInterface $entity, FlexiformFormEntityInterface $plugin) {
  // Add the owner property to created profiles.
  if ($entity->getEntityTypeId() == 'profile' && $plugin->getBaseId() == 'referenced_entity') {
    $base = $plugin->getContextValue('base');
    if ($base->getEntityTypeId() == 'user') {
      $entity->setOwner($base);
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */

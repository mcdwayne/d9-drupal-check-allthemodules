<?php

/**
 * @file
 * Contains \Drupal\rng_date_scheduler\EventDateProviderInterface.
 */

namespace Drupal\rng_date_scheduler;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for event date provider.
 */
interface EventDateProviderInterface {

  /**
   * Get dates for an event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   An event entity.
   *
   * @return \Drupal\rng_date_scheduler\EventDateAccess[]
   *   An array of event date access objects.
   */
  function getDates(EntityInterface $event);

  /**
   * Get date scheduler opinions on registration create access for an event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   An event entity.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result object with cachability.
   */
  function getRegistrationCreateAccess(EntityInterface $event);

  /**
   * @param $entity_type_id
   *   An event type' entity type ID.
   * @param $bundle
   *   An event type' bundle.
   *
   * @return boolean|NULL
   *   FALSE if forbidden. NULL if neutral.
   */
  function getDefaultAccess($entity_type_id, $bundle);

  /**
   * Get field access settings for an event type.
   *
   * @param $entity_type_id
   *   An event type' entity type ID.
   * @param $bundle
   *   An event type' bundle.
   * @param boolean|NULL $status
   *  The status of each field, or NULL to get all.
   *
   * @return array
   *   Field settings from configuration.
   */
  function getFieldAccess($entity_type_id, $bundle, $status = TRUE);

}

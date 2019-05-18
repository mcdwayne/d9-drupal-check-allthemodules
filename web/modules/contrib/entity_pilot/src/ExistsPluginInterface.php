<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Defines an interface for Exists plugins.
 */
interface ExistsPluginInterface {

  /**
   * Determine if an incoming entity already exists on the site.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity manager service.
   * @param \Drupal\Core\Entity\EntityInterface $passenger
   *   Entity being checked.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   The existing entity if one exists, else FALSE.
   */
  public function exists(EntityManagerInterface $entity_manager, EntityInterface $passenger);

  /**
   * Allows plugin to apply modify incoming entities from existing one.
   *
   * @param \Drupal\Core\Entity\EntityInterface $incoming
   *   The entity imported from EntityPilot.
   * @param \Drupal\Core\Entity\EntityInterface $existing
   *   The existing entity that matches.
   */
  public function preApprove(EntityInterface $incoming, EntityInterface $existing);

}

<?php

namespace Drupal\entity_pilot;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Defines an interface for the exists plugin manager.
 */
interface ExistsPluginManagerInterface extends PluginManagerInterface {

  /**
   * Evaluates if an entity exists.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity Manager service.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity being evaluated for existence.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The matching entity.
   */
  public function exists(EntityManagerInterface $entity_manager, EntityInterface $entity);

  /**
   * Allows exists plugins to apply modify incoming entities from existing one.
   *
   * @param \Drupal\Core\Entity\EntityInterface $incoming
   *   The entity imported from EntityPilot.
   * @param \Drupal\Core\Entity\EntityInterface $existing
   *   The existing entity that matches.
   */
  public function preApprove(EntityInterface $incoming, EntityInterface $existing);

}
